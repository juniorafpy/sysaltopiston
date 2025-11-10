<?php

namespace App\Observers;

use App\Models\PresupuestoVenta;
use Illuminate\Support\Facades\Auth;

class PresupuestoVentaObserver
{
    /**
     * Handle the PresupuestoVenta "creating" event.
     */
    public function creating(PresupuestoVenta $presupuestoVenta): void
    {
        $user = Auth::user();

        $presupuestoVenta->cod_sucursal = $user->cod_sucursal ?? null;
        $presupuestoVenta->usuario_alta = $user->name ?? 'Sistema';
        $presupuestoVenta->fec_alta = now();
    }

    /**
     * Handle the PresupuestoVenta "updating" event.
     */
    public function updating(PresupuestoVenta $presupuestoVenta): void
    {
        $presupuestoVenta->usuario_mod = Auth::user()->name ?? 'Sistema';
        $presupuestoVenta->fec_mod = now();
    }
}
