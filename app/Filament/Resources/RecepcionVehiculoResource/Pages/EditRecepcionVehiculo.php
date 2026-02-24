<?php

namespace App\Filament\Resources\RecepcionVehiculoResource\Pages;

use App\Filament\Resources\RecepcionVehiculoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Models\RecepcionInventario;

class EditRecepcionVehiculo extends ViewRecord
{
    protected static string $resource = RecepcionVehiculoResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Cargar el inventario y agregarlo al formulario
        $inventario = RecepcionInventario::where('recepcion_vehiculo_id', $this->record->id)->first();

        if ($inventario) {
            $this->form->fill([
                ...$this->record->toArray(),
                'inventario' => [
                    'extintor' => (bool) $inventario->extintor,
                    'valija' => (bool) $inventario->valija,
                    'rueda_auxilio' => (bool) $inventario->rueda_auxilio,
                    'gato' => (bool) $inventario->gato,
                    'llave_ruedas' => (bool) $inventario->llave_ruedas,
                    'triangulos_seguridad' => (bool) $inventario->triangulos_seguridad,
                    'botiquin' => (bool) $inventario->botiquin,
                    'manual_vehiculo' => (bool) $inventario->manual_vehiculo,
                    'llave_repuesto' => (bool) $inventario->llave_repuesto,
                    'radio_estereo' => (bool) $inventario->radio_estereo,
                    'nivel_combustible' => $inventario->nivel_combustible,
                    'observaciones_inventario' => $inventario->observaciones_inventario,
                ],
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('imprimir')
                ->label('Imprimir Comprobante')
                ->icon('heroicon-o-printer')
                ->color('success')
                ->url(fn () => route('recepcion-vehiculo.pdf', $this->record->id))
                ->openUrlInNewTab(),
        ];
    }
}
