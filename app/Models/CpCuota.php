<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CpCuota extends Model
{
    use HasFactory;

    protected $table = 'cp_cuotas';
    protected $primaryKey = 'id_cuota';
    public $timestamps = false;

    protected $fillable = [
        'id_compra_cabecera',
        'tip_comprobante',
        'ser_comprobante',
        'nro_comprobante',
        'cod_proveedor',
        'nro_cuota',
        'total_cuotas',
        'fec_cuota',
        'fec_vencimiento',
        'monto_cuota',
        'monto_pagado',
        'estado',
        'observacion',
        'usuario_alta',
        'fecha_alta',
        'usuario_mod',
        'fecha_mod',
    ];

    protected $casts = [
        'fec_cuota' => 'date',
        'fec_vencimiento' => 'date',
        'monto_cuota' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'fecha_alta' => 'datetime',
        'fecha_mod' => 'datetime',
    ];

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cuota) {
            if (!$cuota->usuario_alta) {
                $cuota->usuario_alta = Auth::id();
            }
            if (!$cuota->fecha_alta) {
                $cuota->fecha_alta = now();
            }
        });

        static::updating(function ($cuota) {
            $cuota->usuario_mod = Auth::id();
            $cuota->fecha_mod = now();
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

    /**
     * Accessors
     */
    public function getSaldoAttribute()
    {
        return $this->monto_cuota - $this->monto_pagado;
    }

    public function getNumeroCompletoAttribute()
    {
        return "{$this->tip_comprobante}-{$this->ser_comprobante}-{$this->nro_comprobante}";
    }

    /**
     * Scopes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'Pendiente');
    }

    public function scopeVencidas($query)
    {
        return $query->where('estado', 'Pendiente')
            ->where('fec_vencimiento', '<', now()->toDateString());
    }
}
