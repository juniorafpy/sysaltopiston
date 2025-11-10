<?php

namespace App\Filament\Resources\ReclamoResource\Pages;

use App\Filament\Resources\ReclamoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class EditReclamo extends EditRecord
{
    protected static string $resource = ReclamoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Si se marca como resuelto y no tiene fecha de resolución, asignarla
        if (in_array($data['estado'], ['Resuelto', 'Cerrado']) && !$data['fecha_resolucion']) {
            $data['fecha_resolucion'] = now();
            $data['usuario_resolucion'] = Auth::id();
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Reclamo actualizado')
            ->body('Los cambios han sido guardados exitosamente.');
    }
}
