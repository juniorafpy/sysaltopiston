<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresupuestoVentaDetalle extends Model
{
    use HasFactory;

    protected $table = 'presupuesto_venta_detalles';
    public $timestamps = false;

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

    /**
     * Calcular el total: subtotal + monto_impuesto
     */
    public function calculateTotal()
    {
        $subtotal = floatval($this->subtotal ?? 0);
        $montoImpuesto = floatval($this->monto_impuesto ?? 0);
        return $subtotal + $montoImpuesto;
    }

    /**
     * Evento: al guardar, calcular el total automáticamente
     */
    protected static function booted()
    {
        static::saving(function ($model) {
            // Calcular subtotal si no existe
            if (empty($model->subtotal)) {
                $cantidad = floatval($model->cantidad ?? 0);
                $precioUnitario = floatval($model->precio_unitario ?? 0);
                $porcentajeDescuento = floatval($model->porcentaje_descuento ?? 0);

                $subtotal = $cantidad * $precioUnitario;
                $montoDescuento = ($subtotal * $porcentajeDescuento) / 100;
                $model->subtotal = $subtotal - $montoDescuento;
                $model->monto_descuento = $montoDescuento;
            }

            // Calcular monto_impuesto si no existe
            if (empty($model->monto_impuesto) && !empty($model->subtotal)) {
                $porcentajeImpuesto = floatval($model->porcentaje_impuesto ?? 0);
                $model->monto_impuesto = ($model->subtotal * $porcentajeImpuesto) / 100;
            }

            // Calcular total: subtotal + monto_impuesto
            $model->total = floatval($model->subtotal ?? 0) + floatval($model->monto_impuesto ?? 0);
        });

        // Actualizar el total del presupuesto padre después de guardar este detalle
        static::saved(function ($model) {
            if ($model->presupuesto_venta_id) {
                $presupuesto = PresupuestoVenta::find($model->presupuesto_venta_id);
                if ($presupuesto) {
                    $totalDetalles = $presupuesto->detalles()->sum('total');
                    $presupuesto->updateQuietly(['total' => $totalDetalles]);
                }
            }
        });
    }
}
