<?php

namespace App\Filament\Resources\ProveedorResource\Pages;

use App\Filament\Resources\ProveedorResource;
use App\Models\Proveedor;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class ListProveedors extends ListRecords
{
    protected static string $resource = ProveedorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make('crearProveedor')
                ->label('Crear Proveedor')
                ->form([
                    Forms\Components\Select::make('cod_persona')
                        ->label('Persona')
                        ->relationship('personas_pro', 'nombres')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->nombre_completo)
                        ->searchable(['nombres', 'apellidos'])
                        ->preload()
                        ->optionsLimit(5)
                        ->required()
                        ->unique('proveedores', 'cod_persona')
                        ->validationMessages([
                            'unique' => 'La persona seleccionada ya está registrada como proveedor.',
                        ])
                        ->helperText('Busque y seleccione la persona registrada')
                        ->placeholder('Buscar persona...'),

                    Forms\Components\Toggle::make('estado')
                        ->label('Estado Activo')
                        ->helperText('Desactive para dar de baja al proveedor')
                        ->default(true)
                        ->inline(false),

                    Forms\Components\TextInput::make('usuario_alta')
                        ->label('Registrado por')
                        ->default(fn () => auth()->user()->name)
                        ->disabled()
                        ->dehydrated(true),

                    Forms\Components\TextInput::make('fec_alta')
                        ->label('Fecha de Registro')
                        ->default(fn () => now()->format('d/m/Y'))
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->using(function (array $data) {
                    $data['usuario_alta'] = auth()->user()?->name ?? 'sistema';
                    $data['fec_alta'] = now();

                    try {
                        return Proveedor::create($data);
                    } catch (QueryException $exception) {
                        if (($exception->errorInfo[0] ?? null) === '23505') {
                            throw ValidationException::withMessages([
                                'cod_persona' => 'La persona seleccionada ya está registrada como proveedor.',
                            ]);
                        }

                        throw $exception;
                    }
                })
                ->modalHeading('Registrar Proveedor')
                ->modalSubmitActionLabel('Guardar')
                ->modalWidth('lg')
                ->slideOver()
                ->successNotificationTitle('Proveedor creado')
                ->createAnother(false),
        ];
    }
}
