<?php

namespace App\Filament\Resources\MarcasResource\Pages;

use App\Filament\Resources\MarcasResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateMarcas extends CreateRecord
{
    protected static string $resource = MarcasResource::class;

    protected  static bool $canCreateAnother =  false;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manual')
                ->label('Manual de Usuario')
                ->icon('heroicon-o-book-open')
                ->color('info')
                ->url(route('pdf.manual-usuario.marca'))
                ->openUrlInNewTab(),
        ];
    }

 /*   protected function getFormActions(): array
{
    return [
        Actions\CreateAction::make()->CreateAnother(false),
    ];
}*/
}
