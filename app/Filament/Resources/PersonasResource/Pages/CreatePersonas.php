<?php

namespace App\Filament\Resources\PersonasResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\PersonasResource;

class CreatePersonas extends CreateRecord
{
    protected static string $resource = PersonasResource::class;
    protected static ?string $title = 'Registrar Persona';

    protected function getFormActions(): array
    {
        return [
            Action::make('create')
                ->label('Guardar')
                ->action('create')
                ->keyBindings(['mod+s'])
                ->color('warning')
                ->icon('heroicon-o-check'),

            Action::make('cancel')
                ->label('Cancelar')
                ->url($this->getResource()::getUrl('index'))
                ->color('danger')
                ->icon('heroicon-o-x-mark'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Persona registrada exitosamente';
    }
}
