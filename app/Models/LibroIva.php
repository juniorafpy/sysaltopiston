<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibroIva extends Model
{
    use HasFactory;

    protected $table = 'libro_iva';
    protected $primaryKey = 'cod_libro_iva';

    protected $fillable = [
        'fecha',
        'timbrado',
        'numero_factura',
        'ruc_cliente',
        'razon_social',
        'gravado_10',
        'iva_10',
        'gravado_5',
        'iva_5',
        'exentas',
        'total',
        'tipo_operacion',
        'cod_factura'
    ];

    protected $casts = [
        'fecha' => 'date',
        'gravado_10' => 'decimal:2',
        'iva_10' => 'decimal:2',
        'gravado_5' => 'decimal:2',
        'iva_5' => 'decimal:2',
        'exentas' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    /**
     * Relación con factura
     */
    public function factura()
    {
        return $this->belongsTo(Factura::class, 'cod_factura', 'cod_factura');
    }

    /**
     * Scope para ventas
     */
    public function scopeVentas($query)
    {
        return $query->where('tipo_operacion', 'Venta');
    }

    /**
     * Scope para compras
     */
    public function scopeCompras($query)
    {
        return $query->where('tipo_operacion', 'Compra');
    }

    /**
     * Scope para un rango de fechas
     */
    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
    }

    /**
     * Scope para un mes específico
     */
    public function scopeDelMes($query, int $mes, int $anio)
    {
        return $query->whereYear('fecha', $anio)
                     ->whereMonth('fecha', $mes);
    }

    /**
     * Obtiene el total de IVA (10% + 5%)
     */
    public function getTotalIvaAttribute(): float
    {
        return $this->iva_10 + $this->iva_5;
    }

    /**
     * Obtiene el total gravado (10% + 5% + exentas)
     */
    public function getTotalGravadoAttribute(): float
    {
        return $this->gravado_10 + $this->gravado_5 + $this->exentas;
    }
}
