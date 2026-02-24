<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ReporteRecepcionesVehiculos extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Informes';

    protected static ?string $navigationLabel = 'Reporte Recepciones Vehículos';

    protected static ?string $title = 'Reporte Recepciones de Vehículos';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'reporte-recepciones-vehiculos';

    protected static string $view = 'filament.pages.reporte-recepciones-vehiculos';
}
