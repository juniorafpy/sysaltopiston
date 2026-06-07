<?php

namespace App\Filament\Pages;

use App\Models\OrdenServicio;
use App\Models\OrdenServicioDetalle;
use App\Models\ExistenciaArticulo;
use Filament\Forms\Components\{Actions, Repeater, Select, TextInput, Placeholder, Hidden};
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class FinalizarOrdenServicio extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box-arrow-down';
    protected static string $view = 'filament.pages.finalizar-orden-servicio';
    protected static ?string $navigationGroup = 'Gestión Servicios';
    protected static ?string $navigationLabel = 'Finalizar Orden de Servicio';
    protected static ?int $navigationSort = 21;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('orden_servicio_id')
                    ->label('Orden de Servicio')
                    ->options(function () {
                        return OrdenServicio::whereIn('estado_trabajo', ['En Proceso', 'Pausado'])
                            ->with(['cliente.persona', 'detalles'])
                            ->get()
                            ->mapWithKeys(function ($os) {
                                $cliente = $os->cliente?->persona?->nombre_completo ?? 'Sin cliente';
                                return [$os->id => "OS #{$os->id} - {$cliente}"];
                            });
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if (!$state) {
                            $set('detalles', []);
                            return;
                        }

                        $os = OrdenServicio::with('detalles.articulo')->find($state);
                        if ($os) {
                            $detalles = [];
                            foreach ($os->detalles as $detalle) {
                                $detalles[] = [
                                    'id_detalle' => $detalle->id,
                                    'cod_articulo' => $detalle->cod_articulo,
                                    'descripcion' => $detalle->descripcion ?? $detalle->articulo?->descripcion ?? 'N/A',
                                    'cantidad' => $detalle->cantidad,
                                    'cantidad_real' => $detalle->cantidad, // Pre-llenado inteligente
                                ];
                            }
                            $set('detalles', $detalles);
                        }
                    })
                    ->required()
                    ->columnSpanFull(),

                Repeater::make('detalles')
                    ->label('Detalle de Artículos')
                    ->schema([
                        Hidden::make('id_detalle'),
                        TextInput::make('descripcion')
                            ->label('Artículo')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(2),
                        TextInput::make('cantidad')
                            ->label('Reservado')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),
                        TextInput::make('cantidad_real')
                            ->label('Real Utilizada')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->columnSpan(1),
                    ])
                    ->columns(4)
                    ->disabled(fn (callable $get) => !$get('orden_servicio_id'))
                    ->deletable(false)
                    ->reorderable(false)
                    ->addable(false)
                    ->extraAttributes(['style' => 'max-height: 400px; overflow-y: auto;'])
                    ->columnSpanFull(),

                Actions::make([
                    Actions\Action::make('finalizar')
                        ->label('Finalizar Orden')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('¿Está seguro que desea finalizar la orden?')
                        ->modalDescription('Esta acción cerrará la orden de servicio y ajustará el inventario.')
                        ->modalSubmitActionLabel('Sí, finalizar')
                        ->action(function () {
                            $data = $this->form->getState();
                            $this->procesarCierre($data);
                        })
                        ->disabled(fn (callable $get) => empty($get('detalles')) || !$get('orden_servicio_id')),
                ])->fullWidth(),
            ])
            ->statePath('data')
            ->columns(1);
    }

    public function procesarCierre(array $data): void
    {
        $osId = $data['orden_servicio_id'];
        $os = OrdenServicio::find($osId);

        if (!$os) {
            Notification::make()->danger()->title('Error')->body('Orden no encontrada.')->send();
            return;
        }

      
        foreach ($data['detalles'] as $detalleData) {
            $detalle = OrdenServicioDetalle::find($detalleData['id_detalle']);
            if (!$detalle) continue;

            $cantidadReservada = (float) $detalle->cantidad;
            $cantidadReal = (float) ($detalleData['cantidad_real'] ?? 0);

            if ($cantidadReal > $cantidadReservada) {
                $diferenciaExtra = $cantidadReal - $cantidadReservada;
                
                $stock = ExistenciaArticulo::where('cod_articulo', $detalle->cod_articulo)
                    ->where('cod_sucursal', $os->cod_sucursal)
                    ->first();

                if (!$stock || $stock->stock_actual < $diferenciaExtra) {
                    $stockDisponible = $stock ? round($stock->stock_actual) : 0;
                    
                    // Obtener la descripción de forma segura
                    $nombreArticulo = $detalle->descripcion;
                    if (empty($nombreArticulo)) {
                        $articulo = \App\Models\Articulos::find($detalle->cod_articulo);
                        $nombreArticulo = $articulo ? $articulo->descripcion : 'Artículo #' . $detalle->cod_articulo;
                    }
                    
                    $this->dispatch('show-stock-error', message: "El artículo '{$nombreArticulo}' requiere {$diferenciaExtra} unidad extra, en stock actual {$stockDisponible}");
                    
                    return; // Detener el proceso
                }
            }
        }

        // 2. PROCESO DE CIERRE (Si pasa la validación)
        DB::transaction(function () use ($os, $data) {
            foreach ($data['detalles'] as $detalleData) {
                $detalle = OrdenServicioDetalle::find($detalleData['id_detalle']);
                if (!$detalle) continue;

                $cantidadReservada = (float) $detalle->cantidad;
                $cantidadReal = (float) ($detalleData['cantidad_real'] ?? $cantidadReservada);

                // Actualizar detalle
                $detalle->update([
                    'cantidad_real' => $cantidadReal,
                    'stock_descontado' => true,
                    'fecha_descuento_stock' => now(),
                ]);

                // Ajustar Stock
                $stock = ExistenciaArticulo::where('cod_articulo', $detalle->cod_articulo)
                    ->where('cod_sucursal', $os->cod_sucursal)
                    ->first();

                if ($stock) {
                    // 1. Liberar todo lo reservado
                    $stock->stock_reservado = max(0, $stock->stock_reservado - $cantidadReservada);
                    
                    // 2. Ajustar stock_actual
                    // Fórmula: stock_actual = stock_actual - (cantidad_real - cantidad_reservada)
                    // Si real < reservado, resta un negativo (suma al stock)
                    // Si real > reservado, resta el excedente
                    $stock->stock_actual = $stock->stock_actual - ($cantidadReal - $cantidadReservada);
                    
                    $stock->save();
                }
            }

            // Actualizar estado de la Orden
            $os->update([
                'estado_trabajo' => 'Finalizado',
                'fecha_finalizacion_real' => now(),
            ]);
        });

        $this->dispatch('orden-finalizada');
    }
}
