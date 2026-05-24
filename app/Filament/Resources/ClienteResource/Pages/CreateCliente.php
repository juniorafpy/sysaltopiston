<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\QueryException;

class CreateCliente extends CreateRecord
{
    protected static string $resource = ClienteResource::class;
    protected static bool $canCreateAnother = false;

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()
            ->label('Guardar');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }

    protected function afterCreate(): void
    {
        $this->dispatch('swal:success', message: 'Cliente registrado exitosamente.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['usuario_alta'] = auth()->user()->name;
        $data['fec_alta'] = now();

        return $data;
    }

    protected function onValidationError(\Illuminate\Validation\ValidationException $exception): void
    {
        $errors = $exception->validator->errors();

        if ($errors->has('cod_persona')) {
            Notification::make()
                ->warning()
                ->title('Cliente ya registrado')
                ->body('La persona seleccionada ya está registrada como cliente. Por favor seleccione otra persona.')
                ->persistent()
                ->send();
        }

        parent::onValidationError($exception);
    }
}
