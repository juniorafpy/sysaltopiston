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
                        ->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('nombres')
                                ->label('Nombres')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('apellidos')
                                ->label('Apellidos')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('razon_social')
                                ->label('Razón Social (Opcional)')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('nro_documento')
                                ->label('Nro. Documento')
                                ->required()
                                ->unique('personas', 'nro_documento')
                                ->maxLength(20),
                            Forms\Components\TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->maxLength(255),
                            Forms\Components\Select::make('sexo')
                                ->label('Sexo')
                                ->options([
                                    'M' => 'Masculino',
                                    'F' => 'Femenino',
                                ]),
                            Forms\Components\DatePicker::make('fec_nacimiento')
                                ->label('Fecha Nacimiento')
                                ->displayFormat('d/m/Y')
                                ->native(false),
                        ])
                        ->helperText('Selecciona la persona o crea una nueva'),

                    Forms\Components\TextInput::make('nombre')
                        ->label('Nombre/Alias (Opcional)')
                        ->maxLength(255)
                        ->helperText('Nombre alternativo o alias del empleado'),

                    Forms\Components\TextInput::make('email')
                        ->label('Correo Electrónico')
                        ->email()
                        ->maxLength(100)
                        ->helperText('Email corporativo del empleado'),

                    Forms\Components\Select::make('cod_cargo')
                        ->label('Cargo')
                        ->relationship('cargo', 'descripcion')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('descripcion')
                                ->label('Descripción del Cargo')
                                ->required()
                                ->maxLength(100),
                            Forms\Components\Textarea::make('responsabilidades')
                                ->label('Responsabilidades')
                                ->rows(3)
                                ->maxLength(500),
                            Forms\Components\Select::make('area')
                                ->label('Área')
                                ->options([
                                    'Gerencia' => 'Gerencia',
                                    'Administrativa' => 'Administrativa',
                                    'Técnica' => 'Técnica',
                                    'Ventas' => 'Ventas',
                                    'Logística' => 'Logística',
                                    'Servicios Generales' => 'Servicios Generales',
                                ])
                                ->required(),
                            Forms\Components\Toggle::make('activo')
                                ->label('Activo')
                                ->default(true),
                        ])
                        ->createOptionModalHeading('Crear Nuevo Cargo')
                        ->helperText('Selecciona el cargo o crea uno nuevo'),

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
