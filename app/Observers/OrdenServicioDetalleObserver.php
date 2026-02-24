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
        // La reserva de stock se maneja por trigger en BD.
    }

    /**
     * Handle the OrdenServicioDetalle "updated" event.
     */
    public function updated(OrdenServicioDetalle $detalle): void
    {
        // El ajuste por cambios de cantidad se maneja por trigger en BD.
    }

    /**
     * Handle the OrdenServicioDetalle "deleting" event.
     */
    public function deleting(OrdenServicioDetalle $detalle): void
    {
        // La liberación de stock al eliminar se maneja por trigger en BD.
    }
}
