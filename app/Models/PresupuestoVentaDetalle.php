<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresupuestoVentaDetalle extends Model
{
    use HasFactory;

    protected $table = 'presupuesto_venta_detalles';

    protected $fillable = [
        'presupuesto_venta_id',
        'cod_articulo',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'porcentaje_descuento',
        'monto_descuento',
        'porcentaje_impuesto',
        'monto_impuesto',
        'subtotal',
        'total',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'precio_unitario' => 'float',
        'porcentaje_descuento' => 'float',
        'monto_descuento' => 'float',
        'porcentaje_impuesto' => 'float',
        'monto_impuesto' => 'float',
        'subtotal' => 'float',
        'total' => 'float',
    ];

    public function presupuestoVenta()
    {
        return $this->belongsTo(PresupuestoVenta::class);
    }

    public function articulo()
    {
        return $this->belongsTo(Articulos::class, 'cod_articulo', 'cod_articulo');
    }
}
