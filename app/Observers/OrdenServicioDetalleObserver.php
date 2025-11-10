<?php

namespace App\Observers;

use App\Models\OrdenServicioDetalle;

class OrdenServicioDetalleObserver
{
    /**
     * Handle the OrdenServicioDetalle "created" event.
     */
    public function created(OrdenServicioDetalle $detalle): void
    {
        // Si el detalle se crea sin estar reservado, intentar reservar automáticamente
        if (!$detalle->stock_reservado && $detalle->ordenServicio) {
            $detalle->reservarStock();
        }
    }

    /**
     * Handle the OrdenServicioDetalle "updated" event.
     */
    public function updated(OrdenServicioDetalle $detalle): void
    {
        // Si se actualiza la cantidad y ya estaba reservado, ajustar la reserva
        if ($detalle->stock_reservado && $detalle->wasChanged('cantidad')) {
            $cantidadAnterior = $detalle->getOriginal('cantidad');
            $cantidadNueva = $detalle->cantidad;
            $diferencia = $cantidadNueva - $cantidadAnterior;

            if ($diferencia > 0) {
                // Aumentó la cantidad, reservar más
                $articulo = $detalle->articulo;
                $codSucursal = $detalle->ordenServicio->cod_sucursal;

                if ($articulo && $codSucursal) {
                    $articulo->reservarStock($diferencia, $codSucursal);
                }
            } elseif ($diferencia < 0) {
                // Disminuyó la cantidad, liberar el exceso
                $articulo = $detalle->articulo;
                $codSucursal = $detalle->ordenServicio->cod_sucursal;

                if ($articulo && $codSucursal) {
                    $articulo->liberarStock(abs($diferencia), $codSucursal);
                }
            }
        }
    }

    /**
     * Handle the OrdenServicioDetalle "deleting" event.
     */
    public function deleting(OrdenServicioDetalle $detalle): void
    {
        // Si se elimina un detalle, liberar su stock reservado
        if ($detalle->stock_reservado) {
            $detalle->liberarStock();
        }
    }
}
