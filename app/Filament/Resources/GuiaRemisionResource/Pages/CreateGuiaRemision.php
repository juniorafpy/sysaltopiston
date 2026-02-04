<?php

namespace App\Filament\Resources\GuiaRemisionResource\Pages;

use App\Traits\WithSucursalData;
use App\Filament\Resources\GuiaRemisionResource;
use App\Models\Articulos;
use App\Models\ExistenciaArticulo;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateGuiaRemision extends CreateRecord
{
    use WithSucursalData;

    protected static ?string $title = 'Registrar Nota Remisión';
    protected static string $resource = GuiaRemisionResource::class;

    protected static bool $canCreateAnother = false;

    public function mount(): void
    {
        parent::mount();
        $this->initSucursalData();
        $this->initUsuAltaData();
        $this->initEmpleadoData();
        $this->form->fill([
            'nombre_sucursal' => $this->nombre_sucursal,
            'usuario_alta' => $this->nombre_empleado,
            'cod_sucursal' => $this->cod_sucursal,
            'cod_empleado' => $this->cod_empleado,
            'tipo_comprobante' => 'REM',
            'ser_remision' => '001-001',
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // Crear la cabecera de la remisión
            $cabecera = static::getModel()::create([
                'compra_cabecera_id' => $data['compra_cabecera_id'],
                'almacen_id' => $data['almacen_id'],
                'tipo_comprobante' => $data['tipo_comprobante'] ?? 'REM',
                'ser_remision' => $data['ser_remision'] ?? '001-001',
                'numero_remision' => $data['numero_remision'],
                'fecha_remision' => $data['fecha_remision'],
                'cod_sucursal' => $data['cod_sucursal'],
                'cod_empleado' => $data['cod_empleado'] ?? null,
                'usuario_alta' => auth()->user()->name ?? 'Sistema',
                'fec_alta' => now(),
                'estado' => 'P', // P: Pendiente
            ]);

            // Iterar sobre los detalles del repeater
            foreach ($data['detalles'] as $detalle) {
                if ($detalle['cantidad_recibida'] > 0) {
                    // Crear el detalle de la remisión
                    $cabecera->detalles()->create([
                        'articulo_id' => $detalle['articulo_id'],
                        'cantidad_recibida' => $detalle['cantidad_recibida'],
                    ]);

                    // Actualizar el stock en existencia_articulo
                    $existencia = ExistenciaArticulo::where('cod_articulo', $detalle['articulo_id'])
                        ->where('cod_sucursal', $data['cod_sucursal'])
                        ->first();

                    if ($existencia) {
                        // Si existe, incrementar el stock_actual
                        $existencia->increment('stock_actual', $detalle['cantidad_recibida']);
                        $existencia->update([
                            'usuario_mod' => auth()->user()->name ?? 'Sistema',
                            'fec_mod' => now(),
                        ]);
                    } else {
                        // Si no existe, crear nuevo registro de existencia
                        ExistenciaArticulo::create([
                            'cod_articulo' => $detalle['articulo_id'],
                            'cod_sucursal' => $data['cod_sucursal'],
                            'stock_actual' => $detalle['cantidad_recibida'],
                            'usuario_alta' => auth()->user()->name ?? 'Sistema',
                            'fec_alta' => now(),
                        ]);
                    }
                }
            }

            return $cabecera;
        });
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
