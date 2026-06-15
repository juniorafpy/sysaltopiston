<?php

namespace App\Filament\Pages;

use App\Models\OrdenServicio;
use App\Models\RecepcionVehiculo;
use App\Models\EntregaVehiculo;
use App\Models\Cliente;
use App\Models\Mecanico;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class ReportesServicios extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Reportes';
    protected static ?string $navigationGroup = 'Gestión Servicios';
    protected static ?string $title = 'Reportes de Servicios';
    protected static ?int $navigationSort = 99;
    protected static string $view = 'filament.pages.reportes-servicios';

    #[Url]
    public ?string $activeTab = 'ordenes';

    // Filtros Órdenes
    public ?string $orden_fecha_desde = null;
    public ?string $orden_fecha_hasta = null;
    public ?string $orden_cliente = null;
    public ?string $orden_mecanico = null;
    public ?string $orden_estado = null;

    // Filtros Recepciones
    public ?string $recepcion_fecha_desde = null;
    public ?string $recepcion_fecha_hasta = null;
    public ?string $recepcion_cliente = null;
    public ?string $recepcion_estado = null;

    // Filtros Entregas
    public ?string $entrega_fecha_desde = null;
    public ?string $entrega_fecha_hasta = null;
    public ?string $entrega_cliente = null;

    public ?Collection $ordenes_resultados = null;
    public ?Collection $recepciones_resultados = null;
    public ?Collection $entregas_resultados = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('reportes')
                    ->tabs([
                        Tab::make('Órdenes de Servicio')
                            ->icon('heroicon-o-wrench-screwdriver')
                            ->schema([
                                Section::make('Filtros')
                                    ->columns(3)
                                    ->schema([
                                        DatePicker::make('orden_fecha_desde')
                                            ->label('Desde')
                                            ->native(false),

                                        DatePicker::make('orden_fecha_hasta')
                                            ->label('Hasta')
                                            ->native(false),

                                        Select::make('orden_cliente')
                                            ->label('Cliente')
                                            ->searchable()
                                            ->preload()
                                            ->options(function () {
                                                return Cliente::activos()
                                                    ->with('persona')
                                                    ->get()
                                                    ->mapWithKeys(function ($c) {
                                                        return [$c->cod_cliente => $c->nombre_completo ?? 'Cliente #' . $c->cod_cliente];
                                                    });
                                            })
                                            ->native(false)
                                            ->placeholder('Todos'),

                                        Select::make('orden_mecanico')
                                            ->label('Mecánico')
                                            ->searchable()
                                            ->preload()
                                            ->options(function () {
                                                return Mecanico::with('empleado.persona')
                                                    ->get()
                                                    ->mapWithKeys(function ($m) {
                                                        return [$m->cod_mecanico => $m->empleado?->persona?->nombre_completo ?? 'Mecánico #' . $m->cod_mecanico];
                                                    });
                                            })
                                            ->native(false)
                                            ->placeholder('Todos'),

                                        Select::make('orden_estado')
                                            ->label('Estado')
                                            ->options([
                                                'Pendiente' => 'Pendiente',
                                                'En Proceso' => 'En Proceso',
                                                'Finalizado' => 'Finalizado',
                                                'Cancelado' => 'Cancelado',
                                            ])
                                            ->native(false)
                                            ->placeholder('Todos'),
                                    ]),
                                Actions::make([
                                    Action::make('buscar_ordenes')
                                        ->label('Buscar')
                                        ->icon('heroicon-o-magnifying-glass')
                                        ->color('primary')
                                        ->action(fn () => $this->buscarOrdenes()),
                                ])
                                    ->alignment('center'),
                            ]),

                        Tab::make('Recepciones de Vehículos')
                            ->icon('heroicon-o-inbox-arrow-down')
                            ->schema([
                                Section::make('Filtros')
                                    ->columns(3)
                                    ->schema([
                                        DatePicker::make('recepcion_fecha_desde')
                                            ->label('Desde')
                                            ->native(false),

                                        DatePicker::make('recepcion_fecha_hasta')
                                            ->label('Hasta')
                                            ->native(false),

                                        Select::make('recepcion_cliente')
                                            ->label('Cliente')
                                            ->searchable()
                                            ->preload()
                                            ->options(function () {
                                                return Cliente::activos()
                                                    ->with('persona')
                                                    ->get()
                                                    ->mapWithKeys(function ($c) {
                                                        return [$c->cod_cliente => $c->nombre_completo ?? 'Cliente #' . $c->cod_cliente];
                                                    });
                                            })
                                            ->native(false)
                                            ->placeholder('Todos'),

                                        Select::make('recepcion_estado')
                                            ->label('Estado')
                                            ->options([
                                                'Pendiente' => 'Pendiente',
                                                'En Proceso' => 'En Proceso',
                                                'Finalizado' => 'Finalizado',
                                            ])
                                            ->native(false)
                                            ->placeholder('Todos'),
                                    ]),
                                Actions::make([
                                    Action::make('buscar_recepciones')
                                        ->label('Buscar')
                                        ->icon('heroicon-o-magnifying-glass')
                                        ->color('primary')
                                        ->action(fn () => $this->buscarRecepciones()),
                                ])
                                    ->alignment('center'),
                            ]),

                        Tab::make('Entregas de Vehículos')
                            ->icon('heroicon-o-truck')
                            ->schema([
                                Section::make('Filtros')
                                    ->columns(3)
                                    ->schema([
                                        DatePicker::make('entrega_fecha_desde')
                                            ->label('Desde')
                                            ->native(false),

                                        DatePicker::make('entrega_fecha_hasta')
                                            ->label('Hasta')
                                            ->native(false),

                                        Select::make('entrega_cliente')
                                            ->label('Cliente')
                                            ->searchable()
                                            ->preload()
                                            ->options(function () {
                                                return Cliente::activos()
                                                    ->with('persona')
                                                    ->get()
                                                    ->mapWithKeys(function ($c) {
                                                        return [$c->cod_cliente => $c->nombre_completo ?? 'Cliente #' . $c->cod_cliente];
                                                    });
                                            })
                                            ->native(false)
                                            ->placeholder('Todos'),
                                    ]),
                                Actions::make([
                                    Action::make('buscar_entregas')
                                        ->label('Buscar')
                                        ->icon('heroicon-o-magnifying-glass')
                                        ->color('primary')
                                        ->action(fn () => $this->buscarEntregas()),
                                ])
                                    ->alignment('center'),
                            ]),
                    ])
                    ->activeTab(function ($get) {
                        $tabs = ['ordenes' => 0, 'recepciones' => 1, 'entregas' => 2];
                        return $tabs[$this->activeTab] ?? 0;
                    }),
            ]);
    }

    public function buscarOrdenes(): void
    {
        $query = OrdenServicio::query();

        if ($this->orden_fecha_desde) {
            $query->whereDate('fecha_inicio', '>=', $this->orden_fecha_desde);
        }
        if ($this->orden_fecha_hasta) {
            $query->whereDate('fecha_inicio', '<=', $this->orden_fecha_hasta);
        }
        if ($this->orden_cliente) {
            $query->where('cod_cliente', $this->orden_cliente);
        }
        if ($this->orden_mecanico) {
            $query->where('cod_mecanico', $this->orden_mecanico);
        }
        if ($this->orden_estado) {
            $query->where('estado_trabajo', $this->orden_estado);
        }

        $this->ordenes_resultados = $query->with(['cliente', 'mecanicoAsignado.persona', 'recepcionVehiculo.vehiculo', 'entregaVehiculo'])
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        $this->activeTab = 'ordenes';
    }

    public function buscarRecepciones(): void
    {
        $query = RecepcionVehiculo::query();

        if ($this->recepcion_fecha_desde) {
            $query->whereDate('fecha_recepcion', '>=', $this->recepcion_fecha_desde);
        }
        if ($this->recepcion_fecha_hasta) {
            $query->whereDate('fecha_recepcion', '<=', $this->recepcion_fecha_hasta);
        }
        if ($this->recepcion_cliente) {
            $query->where('cod_cliente', $this->recepcion_cliente);
        }
        if ($this->recepcion_estado) {
            $query->where('estado', $this->recepcion_estado);
        }

        $this->recepciones_resultados = $query->with(['cliente', 'vehiculo', 'mecanico'])
            ->orderBy('fecha_recepcion', 'desc')
            ->get();

        $this->activeTab = 'recepciones';
    }

    public function buscarEntregas(): void
    {
        $query = EntregaVehiculo::query();

        if ($this->entrega_fecha_desde) {
            $query->whereDate('fecha_entrega', '>=', $this->entrega_fecha_desde);
        }
        if ($this->entrega_fecha_hasta) {
            $query->whereDate('fecha_entrega', '<=', $this->entrega_fecha_hasta);
        }

        $this->entregas_resultados = $query->with(['ordenServicio.cliente', 'ordenServicio.recepcionVehiculo.vehiculo'])
            ->orderBy('fecha_entrega', 'desc')
            ->get();

        $this->activeTab = 'entregas';
    }

    public function getOrdenesTable()
    {
        if (!$this->ordenes_resultados) {
            return [];
        }
        return $this->ordenes_resultados;
    }

    public function getRecepcionesTable()
    {
        if (!$this->recepciones_resultados) {
            return [];
        }
        return $this->recepciones_resultados;
    }

    public function getEntregasTable()
    {
        if (!$this->entregas_resultados) {
            return [];
        }
        return $this->entregas_resultados;
    }
}
