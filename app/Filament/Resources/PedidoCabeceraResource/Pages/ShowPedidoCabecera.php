<?php

namespace App\Filament\Resources\PedidoCabeceraResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\PedidoCabeceraResource;

use App\Filament\Forms\Components\TextInput;

use Filament\Forms;

class ShowPedidoCabecera extends ViewRecord
{
    protected static string $resource = PedidoCabeceraResource::class;

    // Personalizar el formulario en la vista de detalles
    protected function getFormSchema(): array
    {
        return [
            Forms\Components\TextInput::make('cod_pedido')
                ->label('Código del Pedido')
                ->disabled(), // Solo lectura

            Forms\Components\TextInput::make('nom_empleado')
                ->label('Nombre del Empleado')
                ->disabled(), // Solo lectura

            Forms\Components\TextInput::make('nombre_sucursal')
                ->label('Sucursal')
                ->disabled(), // Solo lectura

            Forms\Components\DateTimePicker::make('fec_pedido')
                ->label('Fecha del Pedido')
                ->disabled(), // Solo lectura

            // Puedes agregar más campos según sea necesario
        ];
    }

    // Acciones del registro
    protected function getActions(): array
    {
        return [
            Actions\Action::make('editar')
                ->label('Editar Pedido')
                //->url(fn($record) => route('filament.resources.pedido-cabeceras.edit', $record))
                ->icon('heroicon-o-pencil')
                ->color('primary'),
        ];
    }

    // Acciones de cabecera (por ejemplo, volver a la lista)
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('volver')
                ->label('Volver')
               //->url(route('filament.resources.pedido-cabeceras.index'))
               ->url(fn ($record) => PedidoCabeceraResource::getUrl('index', ['record' => $record]))

                ->icon('heroicon-o-arrow-left')
                ->color('info'),
        ];
    }
}


