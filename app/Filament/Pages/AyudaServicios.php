<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AyudaServicios extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationGroup = 'Ayuda';
    protected static ?string $navigationLabel = 'Manual Servicios';
    protected static ?string $title = 'Ayuda - Módulo de Servicios';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'ayuda-servicios';
    protected static string $view = 'filament.pages.ayuda-servicios';

    protected static bool $shouldRegisterNavigation = false;
}
