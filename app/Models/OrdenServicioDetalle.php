<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrdenServicioDetalle extends Model
{
    use HasFactory;

    protected $table = 'orden_servicio_detalles';
     public $timestamps = false;


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
     * Rechazar intento de editar campos de precio (solo el mecánico edita cantidad_utilizada)
     * Y asegurar que presupuesto_venta_detalle_id sea correcto
     */
    public function setAttribute($key, $value)
    {
        // Si es presupuesto_venta_detalle_id, asegurar que sea un ID válido del detalle
       // if ($key === 'presupuesto_venta_detalle_id' && $value !== null) {
            // Si viene del repeater del formulario, debe ser el ID del PresupuestoVentaDetalle
            // No permitir que sea asignado un valor incorrecto
         //   if (is_numeric($value)) {
          //      $value = (int) $value;
           //     \Log::debug("OrdenServicioDetalle setAttribute: presupuesto_venta_detalle_id = {$value}");
           // }
       // }

        $preciosProtegidos = [
            'precio_unitario',
            'porcentaje_descuento',
            'monto_descuento',
            'porcentaje_impuesto',
            'monto_impuesto',
            'subtotal',
            'total',
            'usuario_mod',
            'fec_mod',
        ];

        if (in_array($key, $preciosProtegidos)) {
            // Permitir asignación solo si es durante creación (id no existe) o si viene del sistema
            if ($this->exists && !app()->runningInConsole()) {
                return $this;
            }
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Mutator para presupuesto_venta_detalle_id - asegurar que sea INT
     */
    public function setPresupuestoVentaDetalleIdAttribute($value)
    {
        if ($value !== null && is_numeric($value)) {
            $value = (int) $value;
        }
        $this->attributes['presupuesto_venta_detalle_id'] = $value;
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

    /**
     * Calcular precios automáticamente desde presupuesto o artículo
     */
    protected static function booted()
    {
        static::saving(function ($model) {
            if (empty($model->cod_articulo) || floatval($model->cantidad ?? 0) <= 0) {
                return false;
            }

            // VALIDAR presupuesto_venta_detalle_id - si es incorrecto, corregir
            if ($model->presupuesto_venta_detalle_id) {
                $detalle = PresupuestoVentaDetalle::find($model->presupuesto_venta_detalle_id);
                // Si no existe un detalle con ese ID, significa que probablemente es un presupuesto_venta_id
                if (!$detalle && $model->cod_articulo) {
                    // Tratar de encontrar el detalle por cod_articulo y orden_servicio
                    // Si el modelo está asociado a una orden, obtener el presupuesto de esa orden
                    if ($model->orden_servicio_id) {
                        $orden = OrdenServicio::find($model->orden_servicio_id);
                        if ($orden && $orden->presupuesto_venta_id) {
                            // Buscar un detalle en el presupuesto que coincida con el artículo
                            $detalleCorrect = PresupuestoVentaDetalle::where('presupuesto_venta_id', $orden->presupuesto_venta_id)
                                ->where('cod_articulo', $model->cod_articulo)
                                ->first();
                            if ($detalleCorrect) {
                                $model->presupuesto_venta_detalle_id = $detalleCorrect->id;
                            }
                        }
                    }
                }
            }

            // Si es nuevo, traer precios desde presupuesto o artículo
            if (!$model->precio_unitario) {
                // Tratar de obtener del presupuesto
                if ($model->presupuesto_venta_detalle_id) {
                    $presupuestoDetalle = PresupuestoVentaDetalle::find($model->presupuesto_venta_detalle_id);
                    if ($presupuestoDetalle) {
                        $model->precio_unitario = $presupuestoDetalle->precio_unitario;
                        $model->porcentaje_descuento = $presupuestoDetalle->porcentaje_descuento;
                        $model->porcentaje_impuesto = $presupuestoDetalle->porcentaje_impuesto;
                    }
                } elseif ($model->cod_articulo) {
                    // Si no, del catálogo actual
                    $articulo = Articulos::where('cod_articulo', $model->cod_articulo)->first();
                    if ($articulo) {
                        $model->precio_unitario = $articulo->precio_venta ?? 0;
                        $model->porcentaje_impuesto = 19; // IVA por defecto
                    }
                }
            }

            // Calcular subtotal si existe precio y cantidad
            if ($model->precio_unitario && $model->cantidad) {
                $subtotal = $model->cantidad * $model->precio_unitario;
                $porcentajeDescuento = floatval($model->porcentaje_descuento ?? 0);
                $montoDescuento = ($subtotal * $porcentajeDescuento) / 100;

                $model->subtotal = $subtotal - $montoDescuento;
                $model->monto_descuento = $montoDescuento;
            }

            // Calcular impuesto
            if ($model->subtotal) {
                $porcentajeImpuesto = floatval($model->porcentaje_impuesto ?? 0);
                $model->monto_impuesto = ($model->subtotal * $porcentajeImpuesto) / 100;
            }

            // Calcular total
            $model->total = floatval($model->subtotal ?? 0) + floatval($model->monto_impuesto ?? 0);
        });

        // Actualizar total de la OS después de guardar
        static::saved(function ($model) {
            if ($model->orden_servicio_id) {
                $ordenServicio = OrdenServicio::find($model->orden_servicio_id);
                if ($ordenServicio) {
                    $totalDetalles = $ordenServicio->detalles()->sum('total');
                    $ordenServicio->updateQuietly(['total' => $totalDetalles]);
                }
            }
        });
    }
}
