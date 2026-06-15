<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AyudaVentas extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup = 'Ayuda';
    protected static ?string $navigationLabel = 'Manual Ventas';
    protected static ?string $title = 'Ayuda - Módulo de Ventas';
    protected static ?int $navigationSort = 5;
    protected static ?string $slug = 'ayuda-ventas';
    protected static string $view = 'filament.pages.ayuda-ventas';

    protected static bool $shouldRegisterNavigation = false;
}
