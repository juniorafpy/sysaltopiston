<?php

namespace App\Filament\Resources\PresupuestoCabeceraResource\Pages;

use App\Filament\Resources\PresupuestoCabeceraResource;
use App\Traits\WithSucursalData;
use Filament\Resources\Pages\CreateRecord;

class CreatePresupuestoCabecera extends CreateRecord
{
      use WithSucursalData;
    protected static string $resource = PresupuestoCabeceraResource::class;
    protected static bool $canCreateAnother = false;
    protected static ?string $title = 'Crear Presupuesto';

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }


 public function mount(): void
    {
        parent::mount();

        // 3. Llama al método del Trait para inicializar los datos
        $this->initSucursalData();
        $this->initUsuAltaData();
        $this->initEmpleadoData();

        // 4. Rellena el formulario con los datos ya preparados por el Trait
        $this->form->fill([
            'cod_sucursal' => $this->cod_sucursal,
            'nombre_sucursal' => $this->nombre_sucursal,
            'usuario_alta' => $this->usuario_alta,
           //'cod_empleado'=>$this->cod_empleado,
           // 'nombre_empleado'=>$this->nombre_empleado,
        ]);
    }

  protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Defaults de autenticación SOLO en Create
        $data['cod_sucursal'] = $data['cod_sucursal'] ?? auth()->user()?->cod_sucursal;
        $data['usuario_alta'] = auth()->user()->username ?? auth()->user()->name;
        $data['fec_alta']     = now();

        // FORZAR estado PENDIENTE siempre al crear
        $data['estado'] = 'PENDIENTE';

        // Recalcular totales (lado servidor)
        $grav = 0.0; $iva = 0.0;
        foreach (($data['presupuestoDetalles'] ?? []) as &$d) {
            $cantidad  = (float)($d['cantidad'] ?? 0);
            $precio    = (float)($d['precio'] ?? 0);
            $exenta    = (float)($d['exenta'] ?? 0);
            $totalItem = $cantidad * $precio;
            $ivaItem   = max(0, ($totalItem - $exenta)) * 0.10;

            $d['total']     = $totalItem;
            $d['total_iva'] = $ivaItem;

            $grav += $totalItem;
            $iva  += $ivaItem;
        }
        unset($d);

        $data['monto_gravado'] = $grav;
        $data['monto_tot_impuesto'] = $iva;
        $data['monto_general'] = $grav + $iva;

        \Log::info('=== TOTALES CALCULADOS ===', [
            'monto_gravado' => $grav,
            'monto_tot_impuesto' => $iva,
            'monto_general' => $grav + $iva,
        ]);

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // LOG: Ver qué datos llegan
        \Log::info('=== DATOS RECIBIDOS EN handleRecordCreation ===');
        \Log::info('Data completa', ['data' => $data]);
        \Log::info('Detalles', ['detalles' => $data['presupuestoDetalles'] ?? 'NO HAY DETALLES']);

        // Extraer los detalles antes de crear la cabecera
        $detalles = $data['presupuestoDetalles'] ?? [];
        unset($data['presupuestoDetalles']);

        \Log::info('=== DATOS ANTES DE CREAR CABECERA ===', [
            'monto_gravado' => $data['monto_gravado'] ?? 'NO EXISTE',
            'monto_tot_impuesto' => $data['monto_tot_impuesto'] ?? 'NO EXISTE',
            'monto_general' => $data['monto_general'] ?? 'NO EXISTE',
        ]);

        // Crear la cabecera
        $record = static::getModel()::create($data);

        \Log::info('Cabecera creada con ID', [
            'nro_presupuesto' => $record->nro_presupuesto,
            'monto_gravado_guardado' => $record->monto_gravado,
            'monto_tot_impuesto_guardado' => $record->monto_tot_impuesto,
            'monto_general_guardado' => $record->monto_general,
        ]);

        // Guardar los detalles manualmente
        if (!empty($detalles)) {
            \Log::info('Guardando detalles', ['cantidad' => count($detalles)]);

            foreach ($detalles as $index => $detalle) {
                \Log::info("Detalle #{$index}", ['detalle' => $detalle]);

                $detalleCreado = $record->presupuestoDetalles()->create([
                    'cod_articulo' => $detalle['cod_articulo'],
                    'cantidad' => $detalle['cantidad'],
                    'precio' => $detalle['precio'],
                    'total' => $detalle['total'],
                    'total_iva' => $detalle['total_iva'],
                ]);

                \Log::info("Detalle guardado", ['id_detalle' => $detalleCreado->id_detalle]);
            }
        } else {
            \Log::warning('¡NO HAY DETALLES PARA GUARDAR!');
        }

        return $record;
    }

    protected function afterCreate(): void
    {
        // Refuerza consistencia
        $cab = $this->record;
        $grav = 0.0; $iva = 0.0;

        foreach ($cab->presupuestoDetalles as $det) {
            $totalItem = (float)$det->cantidad * (float)$det->precio;
            $ivaItem   = max(0, ($totalItem - (float)($det->exenta ?? 0))) * 0.10;

            if ((float)$det->total !== $totalItem || (float)$det->total_iva !== $ivaItem) {
                $det->update(['total' => $totalItem, 'total_iva' => $ivaItem]);
            }
            $grav += $totalItem;
            $iva  += $ivaItem;
        }

        $cab->update([
            'monto_gravado' => $grav,
            'monto_tot_impuesto' => $iva,
            'monto_general' => $grav + $iva,
        ]);


    }


protected function getFormActions(): array{
    return [
        $this->getCreateFormAction()->label('Guardar'),


        $this->getCancelFormAction()->color('danger'),
    ];
}

}
