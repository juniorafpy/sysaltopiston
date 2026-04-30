<?php

namespace App\Filament\Resources\GuiaRemisionResource\Pages;

use App\Traits\WithSucursalData;
use App\Filament\Resources\GuiaRemisionResource;
use App\Models\Articulos;
use App\Models\ExistenciaArticulo;
use App\Models\GuiaRemisionCabecera;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
            'tipo_comprobante' => 'REM',
            'ser_remision' => '001-001',
            'cod_sucursal' => $this->cod_sucursal,
            'almacen_id' => $this->cod_sucursal,
            'sucursal_destino' => $this->nombre_sucursal ?? 'Sin sucursal',
            'usuario_carga' => auth()->user()->name ?? 'Sistema',
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            // Determinar el proveedor: puede venir de la factura o seleccionado manualmente
            $proveedorId = $data['cod_proveedor'] ?? null;
            
            // Si hay factura seleccionada pero no se estableció proveedor, obtenerlo de la factura
            if (!$proveedorId && !empty($data['compra_cabecera_id'])) {
                $compra = \App\Models\CompraCabecera::find($data['compra_cabecera_id']);
                if ($compra) {
                    $proveedorId = $compra->proveedor?->cod_proveedor;
                }
            }

            // Debug: Verificar que el proveedor está presente
            \Log::info('Verificación de proveedor antes de guardar', [
                'cod_proveedor_en_data' => $data['cod_proveedor'] ?? 'NO PRESENTE',
                'proveedorId_calculado' => $proveedorId ?? 'NULL',
                'compra_cabecera_id' => $data['compra_cabecera_id'] ?? 'NULL',
            ]);

            // Validación adicional del lado del servidor para evitar duplicados
            $existe = GuiaRemisionCabecera::where('numero_remision', $data['numero_remision'])
                ->where('ser_remision', $data['ser_remision'])
                ->where('cod_proveedor', $proveedorId)
                ->exists();
            
            if ($existe) {
                \Filament\Notifications\Notification::make()
                    ->title('❌ Número de Remisión Duplicado')
                    ->body("El número **{$data['numero_remision']}** ya está registrado para este proveedor y serie. Por favor, use un número diferente.")
                    ->danger()
                    ->persistent()
                    ->send();
                
                // Detener la creación sin mostrar stack trace
                $this->halt();
            }

            // Crear la cabecera de la remisión
            $cabecera = static::getModel()::create([
                'compra_cabecera_id' => $data['compra_cabecera_id'] ?? null,
                'tip_factura' => $data['tip_factura'] ?? null,
                'ser_factura' => $data['ser_factura'] ?? null,
                'nro_factura' => $data['nro_factura'] ?? null,
                'cod_proveedor' => $proveedorId,
                'almacen_id' => $data['almacen_id'] ?? $this->cod_sucursal,
                'tipo_comprobante' => $data['tipo_comprobante'] ?? 'REM',
                'ser_remision' => $data['ser_remision'] ?? '001-001',
                'numero_remision' => $data['numero_remision'],
                'timbrado' => $data['timbrado'],
                'fecha_remision' => $data['fecha_remision'],
                'cod_sucursal' => $data['cod_sucursal'] ?? $this->cod_sucursal,
                'usuario_alta' => auth()->user()->name ?? 'Sistema',
                'fec_alta' => now(),
                'estado' => 'P', // P: Pendiente
            ]);

            // Debug: Log de la cabecera creada
            \Log::info('Remisión creada exitosamente', [
                'id' => $cabecera->id,
                'compra_cabecera_id' => $cabecera->compra_cabecera_id,
                'tip_factura' => $cabecera->tip_factura,
                'ser_factura' => $cabecera->ser_factura,
                'nro_factura' => $cabecera->nro_factura,
                'cod_proveedor' => $cabecera->cod_proveedor ?? 'NULL',
                'proveedor' => $cabecera->cod_proveedor,
                'numero' => $cabecera->numero_remision,
            ]);

            // Iterar sobre los detalles del repeater
            $articulosRecepcionados = [];
            foreach ($data['detalles'] as $detalle) {
                if ($detalle['cantidad_recibida'] > 0) {
                    // Crear el detalle de la remisión
                    $detalleCreado = $cabecera->detalles()->create([
                        'articulo_id' => $detalle['articulo_id'],
                        'cantidad_recibida' => $detalle['cantidad_recibida'],
                    ]);

                    // Debug: Log del detalle creado
                    \Log::info('Detalle remisión creado', [
                        'remision_id' => $cabecera->id,
                        'detalle_id' => $detalleCreado->id,
                        'articulo_id' => $detalleCreado->articulo_id,
                        'cantidad' => $detalleCreado->cantidad_recibida,
                    ]);

                    $articulosRecepcionados[] = [
                        'articulo_id' => $detalle['articulo_id'],
                        'cantidad' => $detalle['cantidad_recibida'],
                    ];

                    // Actualizar el stock en existencia_articulo
                    $existencia = ExistenciaArticulo::where('cod_articulo', $detalle['articulo_id'])
                        ->where('cod_sucursal', $data['cod_sucursal'] ?? $this->cod_sucursal)
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
                            'cod_sucursal' => $data['cod_sucursal'] ?? $this->cod_sucursal,
                            'stock_actual' => $detalle['cantidad_recibida'],
                            'usuario_alta' => auth()->user()->name ?? 'Sistema',
                            'fec_alta' => now(),
                        ]);
                    }
                }
            }

            // Cambiar el estado a Aprobado una vez procesados todos los artículos
            $cabecera->update([
                'estado' => 'A', // A: Aprobado/Recepcionado
                'usuario_mod' => auth()->user()->name ?? 'Sistema',
                'fec_mod' => now(),
            ]);

            // Si hay factura asociada, verificar y notificar el estado de recepción
            if ($cabecera->tip_factura && $cabecera->ser_factura && $cabecera->nro_factura) {
                // Buscar la factura usando los campos compuestos
                $compra = \App\Models\CompraCabecera::where('tip_comprobante', $cabecera->tip_factura)
                    ->where('ser_comprobante', $cabecera->ser_factura)
                    ->where('nro_comprobante', $cabecera->nro_factura)
                    ->with('detalles')
                    ->first();
                
                if ($compra) {
                    // Forzar recarga de los detalles para obtener los valores actualizados
                    $compra->refresh();
                    $compra->load('detalles');
                    
                    $porcentaje = $compra->porcentaje_recepcion;
                    
                    // Detalle de artículos recepcionados
                    $detalleTexto = collect($articulosRecepcionados)
                        ->map(fn($item) => "Art. {$item['articulo_id']}: {$item['cantidad']}")
                        ->join(', ');
                    
                    \Filament\Notifications\Notification::make()
                        ->title('✅ Remisión registrada')
                        ->body("Factura {$compra->ser_comprobante}-{$compra->nro_comprobante}: {$porcentaje}% recepcionado | {$detalleTexto}")
                        ->success()
                        ->duration(8000)
                        ->send();
                }
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('✅ Remisión registrada')
                    ->body('Remisión creada sin factura asociada')
                    ->success()
                    ->duration(3000)
                    ->send();
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
