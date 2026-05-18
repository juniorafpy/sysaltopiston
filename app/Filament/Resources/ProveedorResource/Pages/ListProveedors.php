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
            Actions\Action::make('crear')
                ->label('Crear Proveedor')
                ->icon('heroicon-o-plus')
                ->form([
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Select::make('cod_persona')
                                ->label('Persona')
                                ->relationship('personas_pro', 'nombres')
                                ->getOptionLabelFromRecordUsing(fn ($record) => $record->nombre_completo)
                                ->searchable(['nombres', 'apellidos'])
                                ->preload()
                                ->optionsLimit(5)
                                ->required()
                                ->placeholder('Buscar persona...')
                                ->columnSpan(2),

                            Forms\Components\Toggle::make('estado')
                                ->label('Estado Activo')
                                ->helperText('Desactive para dar de baja al proveedor')
                                ->default(true)
                                ->inline(false)
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('usuario_alta')
                                ->label('Usuario Alta')
                                ->default(fn () => auth()->user()->name)
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('fec_alta')
                                ->label('Fecha Alta')
                                ->default(fn () => now()->format('d/m/Y H:i'))
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(1),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data) {
                    if (Proveedor::where('cod_persona', $data['cod_persona'])->exists()) {
                        $this->dispatch('swal:error', message: 'La persona seleccionada ya está registrada como proveedor.');
                        return;
                    }
                    $data['usuario_alta'] = auth()->user()->name;
                    $data['fec_alta'] = now();
                    Proveedor::create($data);
                    \Filament\Notifications\Notification::make()
                        ->title('Proveedor creado exitosamente')
                        ->success()
                        ->send();
                })
                ->modalHeading('Registrar Proveedor')
                ->modalSubmitActionLabel('Guardar')
                ->modalWidth('lg')
                ->slideOver(),
        ];
    }
}
