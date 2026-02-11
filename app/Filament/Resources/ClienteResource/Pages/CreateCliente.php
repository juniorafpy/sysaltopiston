<?php

namespace App\Filament\Resources\ClienteResource\Pages;

use App\Filament\Resources\ClienteResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\QueryException;

class CreateCliente extends CreateRecord
{
    protected static string $resource = ClienteResource::class;

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
