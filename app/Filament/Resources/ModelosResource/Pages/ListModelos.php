<?php

namespace App\Filament\Resources\ModelosResource\Pages;

use App\Filament\Resources\ModelosResource;
use App\Models\Modelos;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListModelos extends ListRecords
{
    protected static string $resource = ModelosResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('crear')
                ->label('Crear Modelo')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\TextInput::make('descripcion')
                        ->label('Modelo')
                        ->maxLength(50)
                        ->required(),
                    Forms\Components\Select::make('cod_marca')
                        ->label('Marca')
                        ->options(function () {
                            return \App\Models\Marcas::pluck('descripcion', 'cod_marca');
                        })
                        ->searchable()
                        ->required(),
                    Forms\Components\TextInput::make('usuario_alta')
                        ->label('Usuario Alta')
                        ->default(fn () => auth()->user()->name)
                        ->disabled()
                        ->dehydrated(false),
                    Forms\Components\TextInput::make('fec_alta')
                        ->label('Fecha Alta')
                        ->default(fn () => now()->format('d/m/Y H:i'))
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->action(function (array $data) {
                    if (Modelos::where('descripcion', $data['descripcion'])->where('cod_marca', $data['cod_marca'])->exists()) {
                        $this->dispatch('swal:error', message: 'El modelo ya esta registrado para esa marca.');
                        return;
                    }
                    $data['usuario_alta'] = auth()->user()->name;
                    $data['fec_alta'] = now();
                    Modelos::create($data);
                    \Filament\Notifications\Notification::make()
                        ->title('Modelo creado exitosamente')
                        ->success()
                        ->send();
                })
                ->modalHeading('Registrar Modelo')
                ->modalSubmitActionLabel('Guardar')
                ->modalWidth('lg'),
        ];
    }
}
