<?php

namespace App\Filament\Resources\EmpleadosResource\Pages;

use App\Filament\Resources\EmpleadosResource;
use App\Models\Empleados;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmpleados extends EditRecord
{
    protected static string $resource = EmpleadosResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function beforeSave(): void
    {
        $codPersona = $this->data['cod_persona'];

        if (Empleados::where('cod_persona', $codPersona)
            ->where('cod_empleado', '!=', $this->record->cod_empleado)
            ->exists()) {
            $this->dispatch('swal:error', message: 'Esta persona ya está registrada como empleado.');
            $this->halt();
        }
    }
}
