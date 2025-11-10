<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenServicioDetalle extends Model
{
    use HasFactory;

    protected $table = 'orden_servicio_detalles';

    protected $fillable = [
        'orden_servicio_id',
        'presupuesto_venta_detalle_id',
        'cod_articulo',
        'descripcion',
        'cantidad',
        'cantidad_utilizada',
        'precio_unitario',
        'porcentaje_descuento',
        'monto_descuento',
        'porcentaje_impuesto',
        'monto_impuesto',
        'subtotal',
        'total',
        'stock_reservado',
        'stock_descontado',
        'fecha_reserva_stock',
        'fecha_descuento_stock',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'cantidad_utilizada' => 'float',
        'precio_unitario' => 'float',
        'porcentaje_descuento' => 'float',
        'monto_descuento' => 'float',
        'porcentaje_impuesto' => 'float',
        'monto_impuesto' => 'float',
        'subtotal' => 'float',
        'total' => 'float',
        'stock_reservado' => 'boolean',
        'stock_descontado' => 'boolean',
        'fecha_reserva_stock' => 'datetime',
        'fecha_descuento_stock' => 'datetime',
    ];

    // Relaciones
    public function ordenServicio(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class);
    }

    public function presupuestoVentaDetalle(): BelongsTo
    {
        return $this->belongsTo(PresupuestoVentaDetalle::class);
    }

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulos::class, 'cod_articulo', 'cod_articulo');
    }

    /**
     * Reserva el stock del artículo en la sucursal de la OS
     */
    public function reservarStock(): bool
    {
        if ($this->stock_reservado) {
            return true; // Ya está reservado
        }

        $articulo = $this->articulo;
        if (!$articulo) {
            return false;
        }

        $codSucursal = $this->ordenServicio->cod_sucursal;
        if (!$codSucursal) {
            return false; // No hay sucursal definida
        }

        if ($articulo->reservarStock($this->cantidad, $codSucursal)) {
            $this->stock_reservado = true;
            $this->fecha_reserva_stock = now();
            return $this->save();
        }

        return false;
    }

    /**
     * Libera el stock reservado en la sucursal de la OS
     */
    public function liberarStock(): bool
    {
        if (!$this->stock_reservado || $this->stock_descontado) {
            return true; // No hay nada que liberar o ya fue descontado
        }

        $articulo = $this->articulo;
        if (!$articulo) {
            return false;
        }

        $codSucursal = $this->ordenServicio->cod_sucursal;
        if (!$codSucursal) {
            return false;
        }

        if ($articulo->liberarStock($this->cantidad, $codSucursal)) {
            $this->stock_reservado = false;
            $this->fecha_reserva_stock = null;
            return $this->save();
        }

        return false;
    }

    /**
     * Descuenta el stock en la sucursal (al facturar)
     */
    public function descontarStock(): bool
    {
        if ($this->stock_descontado) {
            return true; // Ya fue descontado
        }

        $articulo = $this->articulo;
        if (!$articulo) {
            return false;
        }

        $codSucursal = $this->ordenServicio->cod_sucursal;
        if (!$codSucursal) {
            return false;
        }

        if ($articulo->descontarStock($this->cantidad, $codSucursal)) {
            $this->stock_descontado = true;
            $this->fecha_descuento_stock = now();
            return $this->save();
        }

        return false;
    }
}
