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
            Actions\DeleteAction::make()
                ->successNotificationTitle(null)
                ->after(function () {
                    $this->dispatch('swal:success', message: 'Cliente eliminado exitosamente.');
                }),
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
                    $this->dispatch('swal:success', message: 'Cliente desactivado correctamente.');
                }),
            Actions\Action::make('activar')
                ->label('Activar Cliente')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record->estado === 'I')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $record->update(['estado' => 'A']);
                    $this->dispatch('swal:success', message: 'Cliente activado correctamente.');
                }),
        ];
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }

    protected function afterSave(): void
    {
        $this->dispatch('swal:success', message: 'Cliente actualizado exitosamente.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
