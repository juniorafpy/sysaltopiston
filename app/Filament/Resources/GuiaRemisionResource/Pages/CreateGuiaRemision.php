<?php

namespace App\Filament\Resources\GuiaRemisionResource\Pages;

use App\Traits\WithSucursalData;
use App\Filament\Resources\GuiaRemisionResource;
use App\Models\Articulos;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateGuiaRemision extends CreateRecord
{
    use WithSucursalData;

    protected static ?string $title = 'Registrar Nota Remisión';
    protected static string $resource = GuiaRemisionResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->initSucursalData();
        $this->initUsuAltaData();
        $this->initEmpleadoData();
        $this->form->fill([
            'nombre_sucursal' => $this->nombre_sucursal,
            'usuario_alta' => $this->nombre_empleado, // Usamos el nombre del empleado
            'cod_sucursal' => $this->cod_sucursal,
            'cod_empleado' => $this->cod_empleado,
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // Crear la cabecera de la remisión
            $cabecera = static::getModel()::create([
                'compra_cabecera_id' => $data['compra_cabecera_id'],
                'almacen_id' => $data['almacen_id'],
                'numero_remision' => $data['numero_remision'],
                'fecha_remision' => $data['fecha_remision'],
            ]);

            // Iterar sobre los detalles del repeater
            foreach ($data['detalles'] as $detalle) {
                if ($detalle['cantidad_recibida'] > 0) {
                    // Crear el detalle de la remisión
                    $cabecera->detalles()->create([
                        'articulo_id' => $detalle['articulo_id'],
                        'cantidad_recibida' => $detalle['cantidad_recibida'],
                    ]);

                    // Actualizar el stock del artículo
                    $articulo = Articulos::find($detalle['articulo_id']);
                    if ($articulo) {
                        // Asumo que el campo de stock se llama 'stock'. Si es otro nombre, lo podemos cambiar.
                        $articulo->increment('stock', $detalle['cantidad_recibida']);
                    }
                }
            }

            return $cabecera;
        });
    }

    protected function getFormActions(): array{
    return [
        $this->getCreateFormAction()->label('Guardar'),


        $this->getCancelFormAction()->color('danger'),
    ];
}

}
