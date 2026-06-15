<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Faq extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-bottom-center-text';
    protected static ?string $navigationGroup = 'Ayuda';
    protected static ?string $navigationLabel = 'FAQs';
    protected static ?string $title = 'Preguntas Frecuentes';
    protected static ?int $navigationSort = 6;
    protected static ?string $slug = 'faq';
    protected static string $view = 'filament.pages.faq';

    protected static bool $shouldRegisterNavigation = false;
}
