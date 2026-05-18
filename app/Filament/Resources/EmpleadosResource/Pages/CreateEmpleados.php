<?php

namespace App\Filament\Resources\EmpleadosResource\Pages;

use App\Filament\Resources\EmpleadosResource;
use App\Models\Empleados;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEmpleados extends CreateRecord
{
    protected static string $resource = EmpleadosResource::class;

    protected static bool $canCreateAnother = false;

    protected function beforeCreate(): void
    {
        $codPersona = $this->data['cod_persona'];

        if (Empleados::where('cod_persona', $codPersona)->exists()) {
            $this->dispatch('swal:error', message: 'Esta persona ya está registrada como empleado.');
            $this->halt();
        }
    }
}
