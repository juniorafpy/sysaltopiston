<?php

namespace App\Filament\Widgets;

use App\Models\Articulos;
use App\Models\Cliente;
use App\Models\Empleados;
use App\Models\Factura;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Artículos', Articulos::count())
                ->description('Repuestos y productos registrados')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary'),

            Stat::make('Clientes', Cliente::count())
                ->description('Clientes activos en el sistema')
                ->descriptionIcon('heroicon-m-users')
                ->color('success'),

            Stat::make('Facturas', Factura::count())
                ->description('Facturas emitidas')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('warning'),

            Stat::make('Empleados', Empleados::count())
                ->description('Colaboradores registrados')
                ->descriptionIcon('heroicon-m-identification')
                ->color('info'),
        ];
    }
}
