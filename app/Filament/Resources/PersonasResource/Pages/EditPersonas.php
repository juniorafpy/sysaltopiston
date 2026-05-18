<?php

namespace App\Filament\Resources\PersonasResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\PersonasResource;

class EditPersonas extends EditRecord
{
    protected static string $resource = PersonasResource::class;
    protected static ?string $title = 'Editar Persona';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->icon('heroicon-o-trash')
                ->color('danger'),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar')
                ->action('save')
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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $isFisica = ($data['ind_fisica'] === 'S' || $data['ind_fisica'] === true || $data['ind_fisica'] === 1);
        $data['tipo_persona'] = $isFisica ? 'F' : 'J';
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['ind_fisica'] = $data['tipo_persona'] === 'F' ? 'S' : 'N';
        $data['ind_juridica'] = $data['tipo_persona'] === 'J' ? 'S' : 'N';
        $data['ind_activo'] = ($data['ind_activo'] === 'S' || $data['ind_activo'] === true) ? 'S' : 'N';
        unset($data['tipo_persona']);
        return $data;
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return null;
    }

    protected function afterSave(): void
    {
        $this->dispatch('swal:success', message: 'Persona actualizada exitosamente.');
    }
}
