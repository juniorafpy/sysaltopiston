<?php

namespace App\Observers;

use App\Models\NotaCreditoDebitoCompraDetalle;

class NotaCreditoDebitoCompraDetalleObserver
{
    /**
     * Handle the NotaCreditoDebitoCompraDetalle "creating" event.
     */
    public function creating(NotaCreditoDebitoCompraDetalle $detalle): void
    {
        $this->calcularMontoTotal($detalle);
    }

    /**
     * Handle the NotaCreditoDebitoCompraDetalle "updating" event.
     */
    public function updating(NotaCreditoDebitoCompraDetalle $detalle): void
    {
        $this->calcularMontoTotal($detalle);
    }

    /**
     * Calcula el monto total de la línea
     */
    private function calcularMontoTotal(NotaCreditoDebitoCompraDetalle $detalle): void
    {
        if (is_null($detalle->monto_total_linea) || $detalle->monto_total_linea == 0) {
            $cantidad = $detalle->cantidad ?? 0;
            $precioUnitario = $detalle->precio_unitario ?? 0;
            $porcentajeIva = $detalle->porcentaje_iva ?? 10;

            $subtotal = $cantidad * $precioUnitario;
            $montoIva = $subtotal * ($porcentajeIva / 100);
            $detalle->monto_total_linea = $subtotal + $montoIva;
        }
    }
}
