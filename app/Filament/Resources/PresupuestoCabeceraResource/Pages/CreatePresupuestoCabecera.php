<?php

namespace App\Filament\Resources\PresupuestoCabeceraResource\Pages;

use App\Filament\Resources\PresupuestoCabeceraResource;
use App\Traits\WithSucursalData;
use Filament\Resources\Pages\CreateRecord;

class CreatePresupuestoCabecera extends CreateRecord
{
      use WithSucursalData;
    protected static string $resource = PresupuestoCabeceraResource::class;
  protected  static bool $canCreateAnother =  false;

      protected static ?string $title = 'Crear Presupuesto';


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
        $data['usuario_alta'] = auth()->id();
        $data['fec_alta']     = now();

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

        $data['total_gravada'] = $grav;
        $data['tot_iva']       = $iva;
        $data['total']         = $grav + $iva;     // columna real
        // 'total_general' es solo UI; si no existe en BD, no se guardará

        return $data;
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
            'total_gravada' => $grav,
            'tot_iva'       => $iva,
            'total'         => $grav + $iva,
        ]);


    }


protected function getFormActions(): array{
    return [
        $this->getCreateFormAction()->label('Guardar'),


        $this->getCancelFormAction()->color('danger'),
    ];
}

}
