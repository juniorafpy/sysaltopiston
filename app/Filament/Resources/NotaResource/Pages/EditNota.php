<?php

namespace App\Filament\Resources\NotaResource\Pages;

use App\Filament\Resources\NotaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNota extends EditRecord
{
    protected static string $resource = NotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn ($record) => $record->estado === 'Emitida'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Solo permitir edición si está emitida
        if ($this->record->estado !== 'Emitida') {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('No se pueden editar notas anuladas')
                ->danger()
                ->send();
            $this->halt();
        }

        return $data;
    }
}
