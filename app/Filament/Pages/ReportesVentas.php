<?php

namespace App\Filament\Pages;

use App\Models\Factura;
use App\Models\Cobro;
use App\Models\AperturaCaja;
use App\Models\Cliente;
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

class ReportesVentas extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Reportes';
    protected static ?string $navigationGroup = 'Gestión Ventas';
    protected static ?string $title = 'Reportes de Ventas';
    protected static ?int $navigationSort = 99;
    protected static string $view = 'filament.pages.reportes-ventas';

    #[Url]
    public ?string $activeTab = 'facturas';

    // Filtros Facturas
    public ?string $factura_fecha_desde = null;
    public ?string $factura_fecha_hasta = null;
    public ?string $factura_cliente = null;
    public ?string $factura_estado = null;
    public ?string $factura_condicion = null;

    // Filtros Cobros
    public ?string $cobro_fecha_desde = null;
    public ?string $cobro_fecha_hasta = null;
    public ?string $cobro_cliente = null;
    public ?string $cobro_estado = null;

    // Filtros Aperturas
    public ?string $apertura_fecha_desde = null;
    public ?string $apertura_fecha_hasta = null;
    public ?string $apertura_estado = null;

    public ?Collection $facturas_resultados = null;
    public ?Collection $cobros_resultados = null;
    public ?Collection $aperturas_resultados = null;

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
                        Tab::make('Facturas')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Filtros')
                                    ->columns(3)
                                    ->schema([
                                        DatePicker::make('factura_fecha_desde')
                                            ->label('Desde')
                                            ->native(false),

                                        DatePicker::make('factura_fecha_hasta')
                                            ->label('Hasta')
                                            ->native(false),

                                        Select::make('factura_cliente')
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

                                        Select::make('factura_estado')
                                            ->label('Estado')
                                            ->options([
                                                'Emitida' => 'Emitida',
                                                'Anulada' => 'Anulada',
                                                'Pendiente' => 'Pendiente',
                                            ])
                                            ->native(false)
                                            ->placeholder('Todos'),

                                        Select::make('factura_condicion')
                                            ->label('Condición')
                                            ->options([
                                                'Contado' => 'Contado',
                                                'Crédito' => 'Crédito',
                                            ])
                                            ->native(false)
                                            ->placeholder('Todos'),
                                    ]),
                                Actions::make([
                                    Action::make('buscar_facturas')
                                        ->label('Buscar')
                                        ->icon('heroicon-o-magnifying-glass')
                                        ->color('primary')
                                        ->action(fn () => $this->buscarFacturas()),
                                ])
                                    ->alignment('center'),
                            ]),

                        Tab::make('Cobros')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Section::make('Filtros')
                                    ->columns(3)
                                    ->schema([
                                        DatePicker::make('cobro_fecha_desde')
                                            ->label('Desde')
                                            ->native(false),

                                        DatePicker::make('cobro_fecha_hasta')
                                            ->label('Hasta')
                                            ->native(false),

                                        Select::make('cobro_cliente')
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

                                        Select::make('cobro_estado')
                                            ->label('Estado')
                                            ->options([
                                                'Pendiente' => 'Pendiente',
                                                'Completado' => 'Completado',
                                                'Anulado' => 'Anulado',
                                            ])
                                            ->native(false)
                                            ->placeholder('Todos'),
                                    ]),
                                Actions::make([
                                    Action::make('buscar_cobros')
                                        ->label('Buscar')
                                        ->icon('heroicon-o-magnifying-glass')
                                        ->color('primary')
                                        ->action(fn () => $this->buscarCobros()),
                                ])
                                    ->alignment('center'),
                            ]),

                        Tab::make('Aperturas de Caja')
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Section::make('Filtros')
                                    ->columns(3)
                                    ->schema([
                                        DatePicker::make('apertura_fecha_desde')
                                            ->label('Desde')
                                            ->native(false),

                                        DatePicker::make('apertura_fecha_hasta')
                                            ->label('Hasta')
                                            ->native(false),

                                        Select::make('apertura_estado')
                                            ->label('Estado')
                                            ->options([
                                                'Abierta' => 'Abierta',
                                                'Cerrada' => 'Cerrada',
                                            ])
                                            ->native(false)
                                            ->placeholder('Todos'),
                                    ]),
                                Actions::make([
                                    Action::make('buscar_aperturas')
                                        ->label('Buscar')
                                        ->icon('heroicon-o-magnifying-glass')
                                        ->color('primary')
                                        ->action(fn () => $this->buscarAperturas()),
                                ])
                                    ->alignment('center'),
                            ]),
                    ])
                    ->activeTab(function ($get) {
                        $tabs = ['facturas' => 0, 'cobros' => 1, 'aperturas' => 2];
                        return $tabs[$this->activeTab] ?? 0;
                    }),
            ]);
    }

    public function buscarFacturas(): void
    {
        $query = Factura::query();

        if ($this->factura_fecha_desde) {
            $query->whereDate('fecha_factura', '>=', $this->factura_fecha_desde);
        }
        if ($this->factura_fecha_hasta) {
            $query->whereDate('fecha_factura', '<=', $this->factura_fecha_hasta);
        }
        if ($this->factura_cliente) {
            $query->where('cod_cliente', $this->factura_cliente);
        }
        if ($this->factura_estado) {
            $query->where('estado', $this->factura_estado);
        }
        if ($this->factura_condicion) {
            $query->where('condicion_venta', $this->factura_condicion);
        }

        $this->facturas_resultados = $query->with(['cliente'])
            ->orderBy('fecha_factura', 'desc')
            ->get();

        $this->activeTab = 'facturas';
    }

    public function buscarCobros(): void
    {
        $query = Cobro::query();

        if ($this->cobro_fecha_desde) {
            $query->whereDate('fecha_cobro', '>=', $this->cobro_fecha_desde);
        }
        if ($this->cobro_fecha_hasta) {
            $query->whereDate('fecha_cobro', '<=', $this->cobro_fecha_hasta);
        }
        if ($this->cobro_cliente) {
            $query->where('cod_cliente', $this->cobro_cliente);
        }
        if ($this->cobro_estado) {
            $query->where('estado', $this->cobro_estado);
        }

        $this->cobros_resultados = $query->with(['cliente'])
            ->orderBy('fecha_cobro', 'desc')
            ->get();

        $this->activeTab = 'cobros';
    }

    public function buscarAperturas(): void
    {
        $query = AperturaCaja::query();

        if ($this->apertura_fecha_desde) {
            $query->whereDate('fecha_apertura', '>=', $this->apertura_fecha_desde);
        }
        if ($this->apertura_fecha_hasta) {
            $query->whereDate('fecha_apertura', '<=', $this->apertura_fecha_hasta);
        }
        if ($this->apertura_estado) {
            $query->where('estado', $this->apertura_estado);
        }

        $this->aperturas_resultados = $query->with(['caja', 'sucursal'])
            ->orderBy('fecha_apertura', 'desc')
            ->get();

        $this->activeTab = 'aperturas';
    }

    public function getFacturasTable()
    {
        if (!$this->facturas_resultados) {
            return [];
        }
        return $this->facturas_resultados;
    }

    public function getCobrosTable()
    {
        if (!$this->cobros_resultados) {
            return [];
        }
        return $this->cobros_resultados;
    }

    public function getAperturasTable()
    {
        if (!$this->aperturas_resultados) {
            return [];
        }
        return $this->aperturas_resultados;
    }
}
