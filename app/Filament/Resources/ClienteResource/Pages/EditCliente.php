<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class EditCliente extends EditRecord
{
    protected static string $resource = ClienteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\Action::make('desactivar')
                ->label('Desactivar Cliente')
                ->icon('heroicon-o-x-circle')
                ->color('warning')
                ->visible(fn ($record) => $record->estado === 'A')
                ->requiresConfirmation()
                ->modalHeading('Desactivar Cliente')
                ->modalDescription('¿Está seguro que desea desactivar este cliente? Podrá reactivarlo posteriormente.')
                ->action(function ($record) {
                    $record->update(['estado' => 'I']);

                    Notification::make()
                        ->success()
                        ->title('Cliente desactivado')
                        ->body('El cliente ha sido desactivado correctamente.')
                        ->send();
                }),
            Actions\Action::make('activar')
                ->label('Activar Cliente')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record->estado === 'I')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update(['estado' => 'A']);

                    Notification::make()
                        ->success()
                        ->title('Cliente activado')
                        ->body('El cliente ha sido activado correctamente.')
                        ->send();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
