<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ContactoSoporte extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-lifebuoy';
    protected static ?string $navigationGroup = 'Ayuda';
    protected static ?string $navigationLabel = 'Contacto Soporte';
    protected static ?string $title = 'Contacto Soporte';
    protected static ?int $navigationSort = 8;
    protected static ?string $slug = 'contacto-soporte';
    protected static string $view = 'filament.pages.contacto-soporte';

    protected static bool $shouldRegisterNavigation = false;
}
