<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class NotaCreditoDebitoCompra extends Model
{
    use HasFactory;

    protected $table = 'nota_credito_debito_compras';
    protected $primaryKey = 'id_nota';
    public $timestamps = false;

    protected $fillable = [
        'id_compra_cabecera',
        'cod_proveedor',
        'cod_motivo',
        'tip_comprobante',
        'ser_comprobante',
        'timbrado',
        'nro_comprobante',
        'fec_comprobante',
        'observacion',
        'usuario_alta',
        'fecha_alta',
        'usuario_mod',
        'fecha_mod',
    ];

    protected $casts = [
        'fec_comprobante' => 'date',
        'fecha_alta' => 'datetime',
        'fecha_mod' => 'datetime',
    ];

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($nota) {
            if (!$nota->usuario_alta) {
                $nota->usuario_alta = Auth::id();
            }
            if (!$nota->fecha_alta) {
                $nota->fecha_alta = now();
            }
        });

        static::updating(function ($nota) {
            $nota->usuario_mod = Auth::id();
            $nota->fecha_mod = now();
        });
    }

    /**
     * Relaciones
     */
    public function compraCabecera()
    {
        return $this->belongsTo(CompraCabecera::class, 'id_compra_cabecera', 'id_compra_cabecera');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'cod_proveedor', 'cod_proveedor');
    }

    public function motivo()
    {
        return $this->belongsTo(MotivoNotaCreditoDebito::class, 'cod_motivo', 'cod_motivo');
    }

    public function detalles()
    {
        return $this->hasMany(NotaCreditoDebitoCompraDetalle::class, 'id_nota', 'id_nota');
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
     * Accessors
     */
    public function getNumeroCompletoAttribute()
    {
        return "{$this->tip_comprobante}-{$this->ser_comprobante}-{$this->nro_comprobante}";
    }

    public function getTotalNotaAttribute()
    {
        return $this->detalles->sum('monto_total_linea');
    }

    /**
     * Verifica si es nota de crédito
     */
    public function esNotaCredito()
    {
        return $this->tip_comprobante === 'NC';
    }

    /**
     * Verifica si es nota de débito
     */
    public function esNotaDebito()
    {
        return $this->tip_comprobante === 'ND';
    }

    /**
     * Procesa los efectos de la nota según el motivo
     */
    public function procesarEfectos()
    {
        if (!$this->motivo) {
            return;
        }

        // Si afecta stock, actualizar inventario
        if ($this->motivo->afecta_stock) {
            $this->actualizarStock();
        }

        // Si afecta saldo, actualizar cuentas por pagar
        if ($this->motivo->afecta_saldo) {
            $this->actualizarSaldo();
        }
    }

    /**
     * Actualiza el stock según el tipo de nota
     */
    private function actualizarStock()
    {
        foreach ($this->detalles as $detalle) {
            $existencia = \App\Models\ExistenciaArticulo::where('cod_articulo', $detalle->cod_articulo)->first();

            if (!$existencia) {
                continue;
            }

            if ($this->esNotaCredito()) {
                // Nota de crédito: devuelve mercadería, AUMENTA stock
                $existencia->stock_actual += $detalle->cantidad;
            } elseif ($this->esNotaDebito()) {
                // Nota de débito: generalmente no afecta stock, pero si lo hace, DISMINUYE
                $existencia->stock_actual -= $detalle->cantidad;
            }

            $existencia->save();
        }
    }

    /**
     * Actualiza el saldo de cuentas por pagar
     */
    private function actualizarSaldo()
    {
        // Esta lógica dependerá de cómo manejes las cuentas por pagar
        // Por ahora solo dejo el método preparado

        // Ejemplo: Buscar las cuotas de la compra y ajustar montos
        $cuotas = $this->compraCabecera->cuotas()->where('estado', 'Pendiente')->get();

        if ($cuotas->isEmpty()) {
            return;
        }

        $totalNota = $this->total_nota;

        foreach ($cuotas as $cuota) {
            if ($totalNota <= 0) {
                break;
            }

            if ($this->esNotaCredito()) {
                // Nota de crédito: REDUCE la deuda
                $ajuste = min($totalNota, $cuota->monto_cuota - $cuota->monto_pagado);
                $cuota->monto_cuota -= $ajuste;
                $totalNota -= $ajuste;
            } elseif ($this->esNotaDebito()) {
                // Nota de débito: AUMENTA la deuda
                $cuota->monto_cuota += $totalNota;
                $totalNota = 0;
            }

            $cuota->save();
        }
    }
}
