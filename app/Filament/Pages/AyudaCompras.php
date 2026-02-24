<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AyudaCompras extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Ayuda';

    protected static ?string $navigationLabel = 'Manual Compras';

    protected static ?string $title = 'Ayuda - Módulo de Compras';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'ayuda-compras';

    protected static string $view = 'filament.pages.ayuda-compras';
}
