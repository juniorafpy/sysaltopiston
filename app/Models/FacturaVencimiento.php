<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class FacturaVencimiento extends Model
{
    use HasFactory;

    protected $table = 'factura_vencimientos';
    protected $primaryKey = 'cod_vencimiento';

    protected $fillable = [
        'cod_factura',
        'nro_cuota',
        'fecha_vencimiento',
        'monto_cuota',
        'monto_pagado',
        'saldo_pendiente',
        'estado',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'monto_cuota' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'saldo_pendiente' => 'decimal:2',
    ];

    /**
     * Relación con factura
     */
    public function factura()
    {
        return $this->belongsTo(Factura::class, 'cod_factura', 'cod_factura');
    }

    /**
     * Registrar un pago en este vencimiento
     */
    public function registrarPago(float $monto): void
    {
        $this->monto_pagado += $monto;
        $this->saldo_pendiente = $this->monto_cuota - $this->monto_pagado;

        if ($this->saldo_pendiente <= 0) {
            $this->estado = 'Pagado';
            $this->saldo_pendiente = 0;
        }

        $this->save();
    }

    /**
     * Verificar si está vencido
     */
    public function estaVencido(): bool
    {
        return $this->estado === 'Pendiente'
            && $this->fecha_vencimiento->lt(Carbon::now());
    }

    /**
     * Scope para vencimientos pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'Pendiente');
    }

    /**
     * Scope para vencimientos vencidos
     */
    public function scopeVencidos($query)
    {
        return $query->where('estado', 'Pendiente')
                     ->where('fecha_vencimiento', '<', Carbon::now());
    }

    /**
     * Scope para vencimientos pagados
     */
    public function scopePagados($query)
    {
        return $query->where('estado', 'Pagado');
    }
}
