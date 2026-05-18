<?php

namespace App\Filament\Resources\MarcasResource\Pages;

use App\Filament\Resources\MarcasResource;
use App\Models\Marcas;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;

class ListMarcas extends ListRecords
{
    protected static string $resource = MarcasResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('crear')
                ->label('Crear Marca')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\TextInput::make('descripcion')
                        ->label('Marca')
                        ->maxLength(50)
                        ->required(),
                    Forms\Components\Toggle::make('estado')
                        ->label('Estado')
                        ->default(true)
                        ->dehydrateStateUsing(fn ($state) => $state ? 'A' : 'I'),
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
                    if (Marcas::where('descripcion', $data['descripcion'])->exists()) {
                        $this->dispatch('swal:error', message: 'La marca ya está registrada.');
                        return;
                    }
                    $data['usuario_alta'] = auth()->user()->name;
                    $data['fec_alta'] = now();
                    if (!isset($data['estado'])) {
                        $data['estado'] = 'A';
                    }
                    Marcas::create($data);
                    \Filament\Notifications\Notification::make()
                        ->title('Marca creada exitosamente')
                        ->success()
                        ->send();
                })
                ->modalHeading('Registrar Marca')
                ->modalSubmitActionLabel('Guardar')
                ->modalWidth('lg'),
        ];
    }
}
