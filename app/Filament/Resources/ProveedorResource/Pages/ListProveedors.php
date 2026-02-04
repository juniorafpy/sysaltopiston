<?php

namespace App\Filament\Resources\ProveedorResource\Pages;

use App\Filament\Resources\ProveedorResource;
use App\Models\Proveedor;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

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
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Busque y seleccione la persona registrada')
                        ->placeholder('Buscar persona...')
                        ->createOptionForm([
                            Forms\Components\TextInput::make('nombres')
                                ->required()
                                ->label('Nombres'),
                            Forms\Components\TextInput::make('apellidos')
                                ->label('Apellidos'),
                            Forms\Components\TextInput::make('ci_ruc')
                                ->label('CI/RUC')
                                ->required(),
                        ])
                        ->createOptionModalHeading('Crear Nueva Persona'),

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
                        ->default(fn () => now()->format('d/m/Y H:i'))
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->using(function (array $data) {
                    $data['usuario_alta'] = auth()->user()?->name ?? 'sistema';
                    $data['fec_alta'] = now();
                    return Proveedor::create($data);
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
