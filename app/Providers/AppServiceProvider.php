<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use App\Models\Pais;
use App\Observers\PaisObserver;
use App\Models\PresupuestoVenta;
use App\Observers\PresupuestoVentaObserver;
use App\Models\OrdenServicioDetalle;
use App\Observers\OrdenServicioDetalleObserver;
use App\Models\NotaCreditoDebitoCompraDetalle;
use App\Observers\NotaCreditoDebitoCompraDetalleObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Pais::observe(PaisObserver::class);
        PresupuestoVenta::observe(PresupuestoVentaObserver::class);
        OrdenServicioDetalle::observe(OrdenServicioDetalleObserver::class);
        NotaCreditoDebitoCompraDetalle::observe(NotaCreditoDebitoCompraDetalleObserver::class);
    }
}
