<?php

namespace App\Filament\Resources\ModelosResource\Pages;

use App\Filament\Resources\ModelosResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateModelos extends CreateRecord
{
    protected static string $resource = ModelosResource::class;

    protected static bool $canCreateAnother = false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manual')
                ->label('Manual de Usuario')
                ->icon('heroicon-o-book-open')
                ->color('info')
                ->url(route('pdf.manual-usuario.modelo'))
                ->openUrlInNewTab(),
        ];
    }
}
