<?php

namespace App\Filament\Resources\PresupuestoVentaResource\Pages;

use App\Filament\Resources\PresupuestoVentaResource;
use App\Models\Diagnostico;
use Filament\Resources\Pages\CreateRecord;

class CreatePresupuestoVenta extends CreateRecord
{
    protected static string $resource = PresupuestoVentaResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Precargar fecha y estado por defecto
        $data['fecha_presupuesto'] = $data['fecha_presupuesto'] ?? now()->toDateString();
        $data['estado'] = $data['estado'] ?? 'Pendiente';
        $data['cod_sucursal'] = $data['cod_sucursal'] ?? auth()->user()->cod_sucursal;

        $diagnosticoId = request()->integer('diagnostico_id');

        if ($diagnosticoId) {
            $diagnostico = Diagnostico::with('recepcionVehiculo.cliente.persona')->find($diagnosticoId);

            if ($diagnostico) {
                $data['diagnostico_id'] = $diagnostico->id;
                $data['recepcion_vehiculo_id'] = $diagnostico->recepcion_vehiculo_id;
                $data['cod_cliente'] = $diagnostico->recepcionVehiculo?->cliente_id;
                $data['observaciones_diagnostico'] = $diagnostico->diagnostico_mecanico ?? '';
            }
        }

        return $data;
    }

    public function mount(): void
    {
        parent::mount();

        $diagnosticoId = request()->integer('diagnostico_id');

        if ($diagnosticoId) {
            $diagnostico = Diagnostico::with('recepcionVehiculo.cliente.persona')->find($diagnosticoId);

            if ($diagnostico) {
                $this->form->fill([
                    'diagnostico_id' => $diagnostico->id,
                    'recepcion_vehiculo_id' => $diagnostico->recepcion_vehiculo_id,
                    'cod_cliente' => $diagnostico->recepcionVehiculo?->cliente_id,
                    'observaciones_diagnostico' => $diagnostico->diagnostico_mecanico ?? '',
                    'fecha_presupuesto' => now()->toDateString(),
                    'estado' => 'Pendiente',
                    'cod_sucursal' => auth()->user()->cod_sucursal,
                ]);
            }
        } else {
            // Si no viene de diagnóstico, precargar fecha, estado y sucursal
            $this->form->fill([
                'fecha_presupuesto' => now()->toDateString(),
                'estado' => 'Pendiente',
                'cod_sucursal' => auth()->user()->cod_sucursal,
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        [$subtotal, $iva, $total] = PresupuestoVentaResource::summarizeDetalles($data['detalles'] ?? []);

        $data['total'] = $total;

        // Si no tiene cod_cliente pero sí tiene diagnóstico, obtenerlo desde allí
        if (empty($data['cod_cliente']) && !empty($data['diagnostico_id'])) {
            $diagnostico = Diagnostico::with('recepcionVehiculo.cliente')->find($data['diagnostico_id']);
            if ($diagnostico?->recepcionVehiculo?->cliente) {
                $data['cod_cliente'] = $diagnostico->recepcionVehiculo->cliente->cod_cliente;
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

     protected function getFormActions(): array{
        return [
            $this->getCreateFormAction()->label('Guardar'),


            $this->getCancelFormAction()->color('danger'),
        ];
    }
}
