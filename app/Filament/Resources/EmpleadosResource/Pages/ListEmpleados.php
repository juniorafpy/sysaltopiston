<?php

namespace App\Filament\Resources\EmpleadosResource\Pages;

use App\Filament\Resources\EmpleadosResource;
use App\Models\Empleados;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListEmpleados extends ListRecords
{
    protected static string $resource = EmpleadosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('crear')
                ->label('Crear Empleado')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\Select::make('cod_persona')
                        ->label('Persona')
                        ->relationship('persona', 'cod_persona')
                        ->getOptionLabelFromRecordUsing(fn ($record) =>
                            "{$record->nombre_completo} - {$record->nro_documento}"
                        )
                        ->searchable(['nombres', 'apellidos', 'nro_documento'])
                        ->preload()
                        ->required(),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo Electrónico')
                        ->email()
                        ->maxLength(100)
                        ->helperText('Email corporativo del empleado'),

                    Forms\Components\Select::make('cod_cargo')
                        ->label('Cargo')
                        ->relationship('cargo', 'descripcion')
                        ->searchable()
                        ->preload(),

                    Forms\Components\DatePicker::make('fec_alta')
                        ->label('Fecha de Alta')
                        ->default(now())
                        ->required()
                        ->displayFormat('d/m/Y')
                        ->native(false),

                    Forms\Components\Toggle::make('activo')
                        ->label('Activo')
                        ->default(true)
                        ->inline(false)
                        ->helperText('Desactivar para dar de baja al empleado'),
                ])
                ->action(function (array $data) {
                    Empleados::create($data);
                    \Filament\Notifications\Notification::make()
                        ->title('Empleado creado exitosamente')
                        ->success()
                        ->send();
                })
                ->modalHeading('Registrar Empleado')
                ->modalSubmitActionLabel('Guardar')
                ->modalWidth('lg')
                ->slideOver(),
        ];
    }
}
