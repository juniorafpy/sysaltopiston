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
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Select::make('cod_persona')
                                ->label('Persona')
                                ->relationship('persona', 'cod_persona')
                                ->getOptionLabelFromRecordUsing(fn ($record) =>
                                    "{$record->nombre_completo} - {$record->nro_documento}"
                                )
                                ->searchable(['nombres', 'apellidos', 'nro_documento'])
                                ->preload()
                                ->required()
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('email')
                                ->label('Correo Electrónico')
                                ->email()
                                ->maxLength(100)
                                ->helperText('Email corporativo del empleado')
                                ->columnSpan(2),

                            Forms\Components\Select::make('cod_cargo')
                                ->label('Cargo')
                                ->relationship('cargo', 'descripcion')
                                ->searchable()
                                ->preload()
                                ->columnSpan(1),

                            Forms\Components\DatePicker::make('fec_ingreso')
                                ->label('Fecha de Ingreso')
                                ->default(now())
                                ->required()
                                ->displayFormat('d/m/Y')
                                ->native(false)
                                ->columnSpan(1),

                            Forms\Components\Toggle::make('activo')
                                ->label('Activo')
                                ->default(true)
                                ->inline(false)
                                ->helperText('Desactivar para dar de baja al empleado')
                                ->columnSpan(2),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data) {
                    if (Empleados::where('cod_persona', $data['cod_persona'])->exists()) {
                        $this->dispatch('swal:error', message: 'Esta persona ya está registrada como empleado.');
                        return;
                    }
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
