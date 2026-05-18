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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['ind_fisica'] = $data['tipo_persona'] === 'F' ? 'S' : 'N';
        $data['ind_juridica'] = $data['tipo_persona'] === 'J' ? 'S' : 'N';
        $data['ind_activo'] = ($data['ind_activo'] === 'S' || $data['ind_activo'] === true) ? 'S' : 'N';
        $data['usuario_alta'] = auth()->user()->name;
        $data['fec_alta'] = now();
        unset($data['tipo_persona']);
        return $data;
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected function afterCreate(): void
    {
        $this->dispatch('swal:success', message: 'Persona registrada exitosamente.');
    }
}
