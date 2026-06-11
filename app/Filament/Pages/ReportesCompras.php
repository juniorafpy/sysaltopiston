<?php

namespace App\Filament\Pages;

use App\Models\PedidoCabeceras;
use App\Models\OrdenCompraCabecera;
use App\Models\CompraCabecera;
use App\Models\Proveedor;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

class ReportesCompras extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Reportes';
    protected static ?string $navigationGroup = 'Compras';
    protected static ?string $title = 'Reportes de Compras';
    protected static ?int $navigationSort = 99;
    protected static string $view = 'filament.pages.reportes-compras';

    #[Url]
    public ?string $activeTab = 'pedidos';

    // Filtros Pedidos
    public ?string $pedido_fecha_desde = null;
    public ?string $pedido_fecha_hasta = null;
    public ?string $pedido_estado = null;

    // Filtros Ordenes
    public ?string $orden_fecha_desde = null;
    public ?string $orden_fecha_hasta = null;
    public ?string $orden_proveedor = null;
    public ?string $orden_estado = null;

    // Filtros Facturas
    public ?string $factura_fecha_desde = null;
    public ?string $factura_fecha_hasta = null;
    public ?string $factura_numero = null;
    public ?string $factura_tipo = null;

    public ?Collection $pedidos_resultados = null;
    public ?Collection $ordenes_resultados = null;
    public ?Collection $facturas_resultados = null;

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
                        Tab::make('Pedidos de Compra')
                            ->icon('heroicon-o-shopping-cart')
                            ->schema([
                                Section::make('Filtros')
                                    ->columns(3)
                                    ->schema([
                                        DatePicker::make('pedido_fecha_desde')
                                            ->label('Desde')
                                            ->native(false),

                                        DatePicker::make('pedido_fecha_hasta')
                                            ->label('Hasta')
                                            ->native(false),

                                        Select::make('pedido_estado')
                                            ->label('Estado')
                                            ->options([
                                                'PENDIENTE' => 'Pendiente',
                                                'APROBADO' => 'Aprobado',
                                                'RECHAZADO' => 'Rechazado',
                                                'CANCELADO' => 'Cancelado',
                                            ])
                                            ->native(false)
                                            ->placeholder('Todos'),
                                    ]),
                                Actions::make([
                                    Action::make('buscar_pedidos')
                                        ->label('Buscar')
                                        ->icon('heroicon-o-magnifying-glass')
                                        ->color('primary')
                                        ->action(fn () => $this->buscarPedidos()),
                                ])
                                    ->alignment('center'),
                            ]),

                        Tab::make('Ordenes de Compra')
                            ->icon('heroicon-o-clipboard-document-list')
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

                                        Select::make('orden_proveedor')
                                            ->label('Proveedor')
                                            ->searchable()
                                            ->preload()
                                            ->options(function () {
                                                return Proveedor::with('personas_pro')
                                                    ->get()
                                                    ->mapWithKeys(function ($p) {
                                                        return [$p->cod_proveedor => $p->personas_pro?->nombre_completo ?? 'Proveedor #' . $p->cod_proveedor];
                                                    });
                                            })
                                            ->native(false)
                                            ->placeholder('Todos'),

                                        Select::make('orden_estado')
                                            ->label('Estado')
                                            ->options([
                                                'PENDIENTE' => 'Pendiente',
                                                'APROBADO' => 'Aprobado',
                                                'RECIBIDO' => 'Recibido',
                                                'CANCELADO' => 'Cancelado',
                                            ])
                                            ->native(false)
                                            ->placeholder('Todos'),
                                    ])
                                    ->columnSpan(2),
                                Actions::make([
                                    Action::make('buscar_ordenes')
                                        ->label('Buscar')
                                        ->icon('heroicon-o-magnifying-glass')
                                        ->color('primary')
                                        ->action(fn () => $this->buscarOrdenes()),
                                ])
                                    ->alignment('center'),
                            ]),

                        Tab::make('Facturas de Compra')
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

                                        TextInput::make('factura_numero')
                                            ->label('Número Factura')
                                            ->placeholder('Ej: 001-001-0001234')
                                            ->prefixIcon('heroicon-m-hashtag'),

                                        Select::make('factura_tipo')
                                            ->label('Tipo')
                                            ->options([
                                                'FAC' => 'Factura',
                                                'NCR' => 'Nota Crédito',
                                                'NDB' => 'Nota Débito',
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
                    ])
                    ->activeTab(function ($get) {
                        $tabs = ['pedidos' => 0, 'ordenes' => 1, 'facturas' => 2];
                        return $tabs[$this->activeTab] ?? 0;
                    }),
            ]);
    }

    public function buscarPedidos(): void
    {
        $query = PedidoCabeceras::query();

        if ($this->pedido_fecha_desde) {
            $query->whereDate('fec_pedido', '>=', $this->pedido_fecha_desde);
        }
        if ($this->pedido_fecha_hasta) {
            $query->whereDate('fec_pedido', '<=', $this->pedido_fecha_hasta);
        }
        if ($this->pedido_estado) {
            $query->where('estado', $this->pedido_estado);
        }

        $this->pedidos_resultados = $query->with(['detalles', 'ped_empleados.persona', 'sucursal_ped'])
            ->orderBy('fec_pedido', 'desc')
            ->get();

        $this->activeTab = 'pedidos';
    }

    public function buscarOrdenes(): void
    {
        $query = OrdenCompraCabecera::query();

        if ($this->orden_fecha_desde) {
            $query->whereDate('fec_orden', '>=', $this->orden_fecha_desde);
        }
        if ($this->orden_fecha_hasta) {
            $query->whereDate('fec_orden', '<=', $this->orden_fecha_hasta);
        }
        if ($this->orden_proveedor) {
            $query->where('cod_proveedor', $this->orden_proveedor);
        }
        if ($this->orden_estado) {
            $query->where('estado', $this->orden_estado);
        }

        $this->ordenes_resultados = $query->with(['proveedor.personas_pro', 'ordenCompraDetalles', 'sucursale', 'condicionCompra'])
            ->orderBy('fec_orden', 'desc')
            ->get();

        $this->activeTab = 'ordenes';
    }

    public function buscarFacturas(): void
    {
        $query = CompraCabecera::query();

        if ($this->factura_fecha_desde) {
            $query->whereDate('fec_comprobante', '>=', $this->factura_fecha_desde);
        }
        if ($this->factura_fecha_hasta) {
            $query->whereDate('fec_comprobante', '<=', $this->factura_fecha_hasta);
        }
        if ($this->factura_numero) {
            $query->where(function ($q) {
                $q->where('nro_comprobante', 'ilike', '%' . $this->factura_numero . '%')
                  ->orWhere('ser_comprobante', 'ilike', '%' . $this->factura_numero . '%');
            });
        }
        if ($this->factura_tipo) {
            $query->where('tip_comprobante', $this->factura_tipo);
        }

        $this->facturas_resultados = $query->with(['proveedor.personas_pro', 'detalles', 'sucursal', 'condicionCompra'])
            ->orderBy('fec_comprobante', 'desc')
            ->get();

        $this->activeTab = 'facturas';
    }

    public function getPedidosTable()
    {
        if (!$this->pedidos_resultados) {
            return [];
        }
        return $this->pedidos_resultados;
    }

    public function getOrdenesTable()
    {
        if (!$this->ordenes_resultados) {
            return [];
        }
        return $this->ordenes_resultados;
    }

    public function getFacturasTable()
    {
        if (!$this->facturas_resultados) {
            return [];
        }
        return $this->facturas_resultados;
    }
}
