<?php

namespace App\Filament\Resources\ProveedorResource\Pages;

use App\Filament\Resources\ProveedorResource;
use App\Models\Proveedor;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProveedor extends EditRecord
{
    protected static string $resource = ProveedorResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }

    protected function beforeSave(): void
    {
        $codPersona = $this->data['cod_persona'];

        if (Proveedor::where('cod_persona', $codPersona)
            ->where('cod_proveedor', '!=', $this->record->cod_proveedor)
            ->exists()) {
            $this->dispatch('swal:error', message: 'La persona seleccionada ya está registrada como proveedor.');
            $this->halt();
        }
    }
}
