<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class VideosTutoriales extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationGroup = 'Ayuda';
    protected static ?string $navigationLabel = 'Videos Tutoriales';
    protected static ?string $title = 'Videos Tutoriales';
    protected static ?int $navigationSort = 7;
    protected static ?string $slug = 'videos-tutoriales';
    protected static string $view = 'filament.pages.videos-tutoriales';

    protected static bool $shouldRegisterNavigation = false;
}
