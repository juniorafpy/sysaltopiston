<?php

namespace App\Filament\Resources\OrdenServicioResource\Pages;

use App\Filament\Resources\OrdenServicioResource;
use App\Models\PresupuestoVenta;
use App\Models\ExisteStock;
use App\Models\Articulos;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateOrdenServicio extends CreateRecord
{
    protected static string $resource = OrdenServicioResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asignar campos de sistema
        $data['usuario_alta'] = auth()->user()->name ?? 'Sistema';
        $data['fec_alta'] = now();
        $data['cod_sucursal'] = auth()->user()->cod_sucursal ?? null;

        // Si existe presupuesto_venta_id, obtener datos faltantes y VALIDAR STOCK
        if (!empty($data['presupuesto_venta_id'])) {
            $presupuesto = PresupuestoVenta::with([
                'diagnostico.recepcionVehiculo',
                'cliente',
                'detalles.articulo'
            ])->find($data['presupuesto_venta_id']);

            if ($presupuesto && !empty($presupuesto->detalles)) {
                $sucursal = $data['cod_sucursal'];
                $erroresStock = [];

                // Validar contra lo que realmente quedó en el formulario.
                // Si por algún motivo no hay detalles en el form, usar los del presupuesto como fallback.
                $detallesFormulario = collect($data['detalles'] ?? [])
                    ->filter(fn ($detalle) => !empty($detalle['cod_articulo']))
                    ->values();

                $detallesParaValidar = $detallesFormulario->isNotEmpty()
                    ? $detallesFormulario
                    : $presupuesto->detalles;

                $descripcionesArticulos = [];

                // VALIDAR QUE HAY STOCK SUFICIENTE
                foreach ($detallesParaValidar as $detalle) {
                    $codArticulo = data_get($detalle, 'cod_articulo');
                    $cantidad = (float) data_get($detalle, 'cantidad', 0);

                    $descripcion = trim((string) (data_get($detalle, 'descripcion') ?? ''));
                    if ($descripcion === '') {
                        $descripcion = trim((string) (data_get($detalle, 'articulo.descripcion') ?? ''));
                    }

                    if ($descripcion === '' && !empty($codArticulo)) {
                        if (!array_key_exists($codArticulo, $descripcionesArticulos)) {
                            $descripcionesArticulos[$codArticulo] = Articulos::where('cod_articulo', $codArticulo)
                                ->value('descripcion') ?? '';
                        }
                        $descripcion = trim((string) $descripcionesArticulos[$codArticulo]);
                    }

                    if ($descripcion === '') {
                        $descripcion = !empty($codArticulo)
                            ? "Artículo #{$codArticulo}"
                            : 'Artículo sin código';
                    }

                    if (!$codArticulo || $cantidad <= 0) {
                        continue;
                    }

                    $stock = ExisteStock::where('cod_articulo', $codArticulo)
                        ->where('cod_sucursal', $sucursal)
                        ->first();

                    $stockActual = $stock?->stock_actual ?? 0;

                    if ($stockActual < $cantidad) {
                        $erroresStock[] = "{$descripcion}: Disponible {$stockActual}, Requerido {$cantidad}";
                    }
                }

                // Si hay errores, mostrar notificación y cancelar
                if (!empty($erroresStock)) {
                    $htmlErrores = '<div style="max-height: 300px; overflow-y: auto;">';
                    $htmlErrores .= '<ul style="margin: 0; padding-left: 20px;">';
                    foreach ($erroresStock as $error) {
                        $htmlErrores .= '<li style="margin: 5px 0;">' . htmlspecialchars($error) . '</li>';
                    }
                    $htmlErrores .= '</ul></div>';

                    Notification::make()
                        ->danger()
                        ->title('⚠️ Stock Insuficiente')
                        ->body(new \Illuminate\Support\HtmlString($htmlErrores))
                        ->persistent()
                        ->send();

                    $this->halt();
                }

                // Llenar campos que vinieron del formulario
                $data['diagnostico_id'] = $presupuesto->diagnostico_id;
                $data['recepcion_vehiculo_id'] = $presupuesto->recepcion_vehiculo_id;
                $data['cod_cliente'] = $presupuesto->cod_cliente;

                // Obtener mecánico desde recepción
                $recepcion = $presupuesto->recepcionVehiculo ?? $presupuesto->diagnostico?->recepcionVehiculo;
                if ($recepcion?->cod_mecanico) {
                    $data['cod_mecanico'] = $recepcion->cod_mecanico;
                }

                // Total del presupuesto
                $data['total'] = $presupuesto->total;
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // El trigger en la BD maneja la reserva de stock automáticamente
        // No es necesario llamar a reservarStock() aquí

        Notification::make()
            ->success()
            ->title('Orden de servicio creada')
            ->body('Los detalles han sido copiados. El stock será reservado automáticamente.')
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()->label('Guardar'),
            $this->getCancelFormAction()->color('danger'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
