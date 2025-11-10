<?php

namespace App\Filament\Resources\ArticuloResource\Pages;

use App\Filament\Resources\ArticuloResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;

class EditArticulo extends EditRecord
{
    protected static string $resource = ArticuloResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Actualizar usuario y fecha de modificación
        $data['usuario_mod'] = Auth::user()->name;
        $data['fec_mod'] = now();

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Artículo actualizado')
            ->body('Los cambios han sido guardados exitosamente.');
    }
}
