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
        'observacion',
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
    ];

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($compra) {
            if (!$compra->usuario_alta) {
                $compra->usuario_alta = Auth::id();
            }
            if (!$compra->fecha_alta) {
                $compra->fecha_alta = now();
            }
        });

        static::updating(function ($compra) {
            $compra->usuario_mod = Auth::id();
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
        return $this->hasMany(CpCuota::class, 'id_compra_cabecera', 'id_compra_cabecera');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
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
        // Eliminar cuotas existentes si las hay
        $this->cuotas()->delete();

        // Obtener la condición de compra
        $condicion = $this->condicionCompra;
        if (!$condicion) {
            return;
        }

        $cantCuotas = $condicion->cant_cuota;

        // Si es contado (0 cuotas), no generar cuotas
        if ($cantCuotas == 0) {
            return;
        }

        $totalCompra = $this->total_compra;
        $montoCuota = $totalCompra / $cantCuotas;
        $fechaBase = $this->fec_comprobante;

        // Calcular días entre cuotas (dividir los días totales entre la cantidad de cuotas)
        $diasEntreCuotas = $cantCuotas > 1 ? round($condicion->dias_cuotas / $cantCuotas) : $condicion->dias_cuotas;

        for ($i = 1; $i <= $cantCuotas; $i++) {
            // Calcular fecha de vencimiento
            $diasParaVencimiento = $diasEntreCuotas * $i;
            $fechaVencimiento = \Carbon\Carbon::parse($fechaBase)->addDays($diasParaVencimiento);

            CpCuota::create([
                'id_compra_cabecera' => $this->id_compra_cabecera,
                'tip_comprobante' => $this->tip_comprobante,
                'ser_comprobante' => $this->ser_comprobante,
                'nro_comprobante' => $this->nro_comprobante,
                'cod_proveedor' => $this->cod_proveedor,
                'nro_cuota' => $i,
                'total_cuotas' => $cantCuotas,
                'fec_cuota' => $fechaBase,
                'fec_vencimiento' => $fechaVencimiento,
                'monto_cuota' => $montoCuota,
                'monto_pagado' => 0,
                'estado' => 'Pendiente',
            ]);
        }
    }
}
