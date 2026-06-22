<?php

namespace App\Filament\Resources\PresupuestoVentaResource\Pages;

use App\Filament\Resources\PresupuestoVentaResource;
use App\Models\Diagnostico;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreatePresupuestoVenta extends CreateRecord
{
    protected static string $resource = PresupuestoVentaResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Precargar fecha y estado por defecto solo si no existen
        $data['fecha_presupuesto'] = $data['fecha_presupuesto'] ?? now()->toDateString();
        $data['estado'] = $data['estado'] ?? 'Pendiente';
        $data['cod_sucursal'] = $data['cod_sucursal'] ?? auth()->user()->cod_sucursal;
        $data['cod_tipo_venta'] = $data['cod_tipo_venta'] ?? (request()->has('diagnostico_id') ? 2 : 1);

        // Solo cargar datos del diagnóstico si aún no están en el formulario
        // (evita sobreescribir al re-renderizar)
        $diagnosticoId = request()->integer('diagnostico_id');

        if ($diagnosticoId && empty($data['diagnostico_id'])) {
            $diagnostico = Diagnostico::with('recepcionVehiculo.cliente.persona')->find($diagnosticoId);

            if ($diagnostico) {
                $data['diagnostico_id'] = $diagnostico->id;
                $data['diagnostico_display'] = $diagnostico->id;
                $data['recepcion_vehiculo_id'] = $diagnostico->recepcion_vehiculo_id;
                $data['cod_cliente_val'] = $diagnostico->recepcionVehiculo?->cliente_id;
                
                if ($diagnostico?->recepcionVehiculo?->cliente?->persona) {
                    $persona = $diagnostico->recepcionVehiculo->cliente->persona;
                    $data['cliente_nombre'] = $persona->razon_social ?: trim($persona->nombres . ' ' . ($persona->apellidos ?? ''));
                }
                
                $data['observaciones_diagnostico'] = $diagnostico->diagnostico_mecanico ?? '';
            }
        }

        return $data;
    }

    public function mount(): void
    {
        parent::mount();

        $diagnosticoId = request()->integer('diagnostico_id');

        $user = auth()->user();
        $codSucursal = $user?->cod_sucursal;
        $sucursal = $codSucursal ? \App\Models\Sucursal::find($codSucursal) : null;
        $sucursalDisplay = $sucursal?->descripcion ?? 'Sin sucursal';

        if ($diagnosticoId) {
            $diagnostico = Diagnostico::with('recepcionVehiculo.cliente.persona')->find($diagnosticoId);

            if ($diagnostico) {
                $clienteNombre = '';
                if ($diagnostico?->recepcionVehiculo?->cliente?->persona) {
                    $persona = $diagnostico->recepcionVehiculo->cliente->persona;
                    $clienteNombre = $persona->razon_social ?: trim($persona->nombres . ' ' . ($persona->apellidos ?? ''));
                }

                $this->form->fill([
                    'diagnostico_id' => $diagnostico->id,
                    'diagnostico_display' => $diagnostico->id,
                    'recepcion_vehiculo_id' => $diagnostico->recepcion_vehiculo_id,
                    'cod_cliente_val' => $diagnostico->recepcionVehiculo?->cliente_id,
                    'cliente_nombre' => $clienteNombre,
                    'observaciones_diagnostico' => $diagnostico->diagnostico_mecanico ?? '',
                    'fecha_presupuesto' => now()->toDateString(),
                    'estado' => 'Pendiente',
                    'cod_sucursal' => $codSucursal,
                    'sucursal_display' => $sucursalDisplay,
                    'cod_tipo_venta' => 2, // OS por defecto cuando viene de diagnóstico
                ]);
            }
        } else {
            // Si no viene de diagnóstico, precargar fecha, estado, sucursal y tipo de venta Mostrador
            $this->form->fill([
                'fecha_presupuesto' => now()->toDateString(),
                'estado' => 'Pendiente',
                'cod_sucursal' => $codSucursal,
                'sucursal_display' => $sucursalDisplay,
                'cod_tipo_venta' => 1, // Mostrador por defecto
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        [$subtotal, $iva, $total] = PresupuestoVentaResource::summarizeDetalles($data['detalles'] ?? []);

        $data['total'] = $total;

        // Si no tiene cod_cliente pero sí tiene cod_cliente_val (desde diagnóstico), usarlo
        if (empty($data['cod_cliente']) && !empty($data['cod_cliente_val'])) {
            $data['cod_cliente'] = $data['cod_cliente_val'];
        }
        
        // Si aún no tiene cod_cliente pero sí tiene diagnóstico, obtenerlo desde allí
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

     protected function getCreatedNotification(): ?Notification
    {
        return null;
    }

    protected function afterCreate(): void
    {
        $this->dispatch('swal:success-modal', [
            'title' => 'Presupuesto registrado',
            'message' => "Presupuesto Nro: {$this->record->id} registrado correctamente"
        ]);
    }

    protected function getFormActions(): array{
        return [
            $this->getCreateFormAction()->label('Guardar'),
            $this->getCancelFormAction()->color('danger'),
        ];
    }
}
