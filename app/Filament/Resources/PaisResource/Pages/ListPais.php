<?php

namespace App\Filament\Resources\PaisResource\Pages;

use App\Filament\Resources\PaisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Pais;
use Filament\Forms;

class ListPais extends ListRecords
{
    protected static string $resource = PaisResource::class;

     protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make('crearPais')
                ->label('Crear país')
                ->form([
                    Forms\Components\TextInput::make('descripcion')
                        ->label('Descripción')
                        ->required()
                        ->maxLength(50),
                    Forms\Components\TextInput::make('gentilicio')
                        ->label('Gentilicio')
                        ->required()
                        ->maxLength(20),
                    Forms\Components\TextInput::make('abreviatura')
                        ->label('Abreviatura')
                        ->required()
                        ->maxLength(3),
                    Forms\Components\Toggle::make('estado')
                        ->label('Activo')
                        ->helperText('Activado = S, desactivado = N')
                        ->default(true)
                        ->formatStateUsing(fn ($state) => $state !== 'N')
                        ->dehydrateStateUsing(fn ($state) => $state ? 'S' : 'N')
                        ->inline(false),
                    Forms\Components\TextInput::make('usuario_alta')
                        ->label('Usuario Alta')
                        ->default(fn () => auth()->user()->name)
                        ->disabled()
                        ->dehydrated(true),
                    Forms\Components\TextInput::make('fec_alta')
                        ->label('Fecha Alta')
                        ->default(fn () => now()->format('d/m/Y H:i'))
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->using(function (array $data) {
                    $data['usuario_alta'] = auth()->user()?->name ?? 'sistema';
                    $data['fec_alta']     = now();
                    $data['estado'] ??= 'S';

                    return Pais::create($data);
                })
                ->modalHeading('Registrar país')
                ->modalSubmitActionLabel('Guardar')
                ->modalWidth('lg')
                ->slideOver()
                ->successNotificationTitle('País creado')
                ->createAnother(false),
        ];
    }
}
