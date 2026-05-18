<?php

namespace App\Filament\Resources\ProveedorResource\Pages;

use App\Filament\Resources\ProveedorResource;
use App\Models\Proveedor;
use Filament\Resources\Pages\CreateRecord;

class CreateProveedor extends CreateRecord
{
    protected static string $resource = ProveedorResource::class;

    protected static bool $canCreateAnother = false;

    protected function beforeCreate(): void
    {
        $codPersona = $this->data['cod_persona'];

        if (Proveedor::where('cod_persona', $codPersona)->exists()) {
            $this->dispatch('swal:error', message: 'La persona seleccionada ya está registrada como proveedor.');
            $this->halt();
        }
    }
}
