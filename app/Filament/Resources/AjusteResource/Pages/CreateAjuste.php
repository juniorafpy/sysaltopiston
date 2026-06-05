<?php

namespace App\Filament\Resources\AjusteResource\Pages;

use App\Filament\Resources\AjusteResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateAjuste extends CreateRecord
{
    protected static string $resource = AjusteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreateFormAction(): Actions\Action
    {
        return Actions\Action::make('create')
            ->label('Guardar')
            ->submit('create')
            ->keyBindings(['mod+s']);
    }

    protected function getCreateAnotherFormAction(): Actions\Action
    {
        return Actions\Action::make('createAnother')
            ->visible(false);
    }

    protected function getCancelButtonAction(): Actions\Action
    {
        return Actions\Action::make('cancel')
            ->label('Cancelar')
            ->color('danger')
            ->url($this->getResource()::getUrl('index'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['detalles'])) {
            $this->dispatch('swal:error', message: 'Debe agregar al menos un artículo al ajuste.');
            $this->halt();
        }

        return $data;
    }

    public function create(bool $another = false): void
    {
        try {
            parent::create($another);
        } catch (ValidationException $e) {
            $errors = collect($e->validator->errors())->flatten()->join(' ');
            $this->dispatch('swal:error', message: $errors);
        }
    }
}
