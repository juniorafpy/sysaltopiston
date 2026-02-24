<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Informes extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Informes';

    protected static ?string $navigationLabel = 'Reporte Pedido Compra';

    protected static ?string $title = 'Reporte Pedido Compra';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'informes';

    protected static string $view = 'filament.pages.informes';
}
