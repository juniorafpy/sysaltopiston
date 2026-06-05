<?php

namespace App\Filament\Resources\AjusteResource\Pages;

use App\Filament\Resources\AjusteResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditAjuste extends EditRecord
{
    protected static string $resource = AjusteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (empty($data['detalles'])) {
            $this->dispatch('swal:error', message: 'Debe agregar al menos un artículo al ajuste.');
            $this->halt();
        }

        return $data;
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        try {
            parent::save($shouldRedirect, $shouldSendSavedNotification);
        } catch (ValidationException $e) {
            $errors = collect($e->validator->errors())->flatten()->join(' ');
            $this->dispatch('swal:error', message: $errors);
        }
    }
}
