<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CompraCabecera extends Model
{
    use HasFactory;

    protected $table = 'cm_compras_cabecera';
    protected $primaryKey = 'id_compra_cabecera';
    public $timestamps = false;

    protected $fillable = [
        'cod_sucursal',
        'fec_comprobante',
        'cod_proveedor',
        'tip_comprobante',
        'ser_comprobante',
        'timbrado',
        'nro_comprobante',
        'cod_condicion_compra',
        'fec_vencimiento',
        'nro_oc_ref',
        'cod_impuesto',
        'observacion',
        'monto_gravado',
        'monto_tot_impuesto',
        'monto_general',
        'usuario_alta',
        'fecha_alta',
        'usuario_mod',
        'fecha_mod',
    ];

    protected $casts = [
        'fec_comprobante' => 'date',
        'fec_vencimiento' => 'date',
        'fecha_alta' => 'datetime',
        'fecha_mod' => 'datetime',
        'monto_gravado' => 'decimal:2',
        'monto_tot_impuesto' => 'decimal:2',
        'monto_general' => 'decimal:2',
    ];

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($compra) {
            if (!$compra->usuario_alta) {
                $compra->usuario_alta = auth()->user()->name ?? 'Sistema';
            }
            if (!$compra->fecha_alta) {
                $compra->fecha_alta = now();
            }
        });

        static::updating(function ($compra) {
            $compra->usuario_mod = auth()->user()->name ?? 'Sistema';
            $compra->fecha_mod = now();
        });
    }

    /**
     * Relaciones
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'cod_proveedor', 'cod_proveedor');
    }

    public function condicionCompra()
    {
        return $this->belongsTo(CondicionCompra::class, 'cod_condicion_compra', 'cod_condicion');
    }

    public function detalles()
    {
        return $this->hasMany(CompraDetalle::class, 'id_compra_cabecera', 'id_compra_cabecera');
    }

    public function cuotas()
    {
        return $this->hasMany(CpCuota::class, 'nro_comprobante', 'nro_comprobante')
            ->where('ser_comprobante', $this->ser_comprobante)
            ->where('cod_proveedor', $this->cod_proveedor);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    public function impuesto()
    {
        return $this->belongsTo(Impuesto::class, 'cod_impuesto', 'cod_impuesto');
    }

    public function usuarioAlta()
    {
        return $this->belongsTo(User::class, 'usuario_alta', 'id');
    }

    public function usuarioMod()
    {
        return $this->belongsTo(User::class, 'usuario_mod', 'id');
    }

    /**
     * Verifica si la compra está completamente recepcionada
     */
    public function getEstaCompletamenteRecepcionadaAttribute()
    {
        foreach ($this->detalles as $detalle) {
            if ($detalle->cantidad_pendiente > 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Obtiene el porcentaje de recepción
     */
    public function getPorcentajeRecepcionAttribute()
    {
        $totalFacturado = $this->detalles->sum('cantidad');
        if ($totalFacturado == 0) return 0;

        $totalRecibido = $this->detalles->sum('cantidad_recibida');
        return round(($totalRecibido / $totalFacturado) * 100, 2);
    }

    /**
     * Obtiene el estado de recepción de la factura
     */
    public function getEstadoRecepcionAttribute()
    {
        if ($this->esta_completamente_recepcionada) {
            return 'RECEPCIONADO';
        }

        if ($this->porcentaje_recepcion > 0) {
            return 'PARCIAL';
        }

        return 'PENDIENTE';
    }

    /**
     * Accessors
     */
    public function getNumeroCompletoAttribute()
    {
        return "{$this->tip_comprobante}-{$this->ser_comprobante}-{$this->nro_comprobante}";
    }

    public function getTotalCompraAttribute()
    {
        return $this->detalles->sum('monto_total_linea');
    }

    /**
     * Genera las cuotas para la compra según la condición de compra
     */
    public function generarCuotas()
    {
        // Eliminar cuotas existentes si las hay (por nro_comprobante, serie y proveedor)
        CpCuota::where('nro_comprobante', $this->nro_comprobante)
            ->where('ser_comprobante', $this->ser_comprobante)
            ->where('cod_proveedor', $this->cod_proveedor)
            ->delete();

        // Obtener la condición de compra
        $condicion = $this->condicionCompra;
        if (!$condicion) {
            return;
        }

        $cantCuotas = $condicion->cant_cuota;

        // Si es contado (0 o 1 cuota), no generar cuotas
        if (!$cantCuotas || $cantCuotas <= 0) {
            return;
        }

        // Calcular el total de la compra desde los detalles
        $totalCompra = $this->detalles()->sum('monto_total_linea');
        
        if ($totalCompra <= 0) {
            return;
        }

        $montoCuota = $totalCompra / $cantCuotas;
        $fechaBase = $this->fec_comprobante;
        $fechaVencimiento = $this->fec_vencimiento;

        // Calcular días entre la fecha de comprobante y la fecha de vencimiento
        $diasTotal = \Carbon\Carbon::parse($fechaBase)->diffInDays(\Carbon\Carbon::parse($fechaVencimiento));
        
        // Si hay múltiples cuotas, dividir los días proporcionalmente
        $diasEntreCuotas = $cantCuotas > 1 ? round($diasTotal / $cantCuotas) : $diasTotal;

        for ($i = 1; $i <= $cantCuotas; $i++) {
            // Calcular fecha de vencimiento para cada cuota
            $diasParaVencimiento = $diasEntreCuotas * $i;
            $fechaVencimientoCuota = \Carbon\Carbon::parse($fechaBase)->addDays($diasParaVencimiento);

            CpCuota::create([
                'tip_comprobante' => $this->tip_comprobante,
                'ser_comprobante' => $this->ser_comprobante,
                'nro_comprobante' => $this->nro_comprobante,
                'cod_proveedor' => $this->cod_proveedor,
                'nro_cuota' => $i,
                'total_cuotas' => $cantCuotas,
                'fec_cuota' => $fechaBase,
                'fec_vencimiento' => $fechaVencimientoCuota,
                'monto_cuota' => round($montoCuota, 2),
                'monto_pagado' => 0,
                'estado' => 'Pendiente',
            ]);
        }
    }

    /**
     * Registra la compra en el libro IVA
     */
    public function registrarLibroIva()
    {
        // Eliminar registro existente si lo hay
        LibroIvaCompra::where('nro_comprobante', $this->nro_comprobante)
            ->where('ser_comprobante', $this->ser_comprobante)
            ->where('cod_proveedor', $this->cod_proveedor)
            ->delete();

        // Calcular totales por tipo de IVA (solo el IVA extraído)
        $iva10Total = 0;
        $iva5Total = 0;
        $exentaTotal = 0;
        $iva10Impuesto = 0;
        $iva5Impuesto = 0;

        foreach ($this->detalles as $detalle) {
            $porcentaje = (float) $detalle->porcentaje_iva;
            $montoTotal = (float) $detalle->monto_total_linea;
            
            if ($porcentaje == 10) {
                // Total con IVA 10%
                $iva10Total += $montoTotal;
                // IVA extraído = total / 11
                $iva10Impuesto += ($montoTotal / 11);
            } elseif ($porcentaje == 5) {
                // Total con IVA 5%
                $iva5Total += $montoTotal;
                // IVA extraído = total / 21
                $iva5Impuesto += ($montoTotal / 21);
            } else {
                // Exenta (sin IVA)
                $exentaTotal += $montoTotal;
            }
        }

        $total = $iva10Total + $iva5Total + $exentaTotal;

        // Crear registro en libro IVA
        LibroIvaCompra::create([
            'cod_sucursal' => $this->cod_sucursal,
            'tip_comprobante' => 'FAC',
            'nro_comprobante' => $this->nro_comprobante,
            'ser_comprobante' => $this->ser_comprobante,
            'cod_proveedor' => $this->cod_proveedor,
            'fec_comprobante' => $this->fec_comprobante,
            'iva10' => $iva10Impuesto,  // Solo el IVA extraído
            'iva5' => $iva5Impuesto,    // Solo el IVA extraído
            'exenta' => $exentaTotal,
            'total' => $total,
        ]);
    }
}
