<?php

namespace App\Filament\Pages;

use App\Models\Cliente;
use App\Models\EntidadBancaria;
use App\Models\Factura;
use Filament\Forms\Components\{CheckboxList, Grid, Placeholder, Repeater, Section, Select, TextInput};
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class RegistrarCobro extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static string $view = 'filament.pages.registrar-cobro';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?int $navigationSort = 5;

    public array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- FILA 1: Cabecera y Facturas (Alturas Sincronizadas) ---
                Grid::make(3)->schema([
                    Section::make('Datos del Cobro')
                        ->schema([
                            Select::make('cliente_id')
                                ->label('Cliente')
                                ->options(fn () => Cliente::activos()
                                    ->with('persona')
                                    ->get()
                                    ->mapWithKeys(fn($c) => [$c->cod_persona => $c->nombre_completo])
                                    ->toArray()
                                )
                                ->searchable()
                                ->live()
                                ->required(),
                            TextInput::make('fecha_actual')
                                ->label('Fecha')
                                ->default(now()->format('Y-m-d'))
                                ->readOnly()
                                ->disabled(),
                            TextInput::make('caja_aperturada')
                                ->label('Caja Aperturada')
                                ->default(fn() => auth()->user()->name)
                                ->readOnly()
                                ->disabled(),
                        ])
                        ->columnSpan(1),

                    Section::make('Facturas Pendientes')
                        ->schema([
                            CheckboxList::make('facturas_seleccionadas')
                                ->label('')
                                ->options(function (callable $get) {
                                    $clienteId = $get('cliente_id');
                                    if (!$clienteId) return [];

                                    return Factura::where('cod_cliente', $clienteId)
                                        ->where('condicion_venta', 'Crédito')
                                        ->where('estado', 'Emitida')
                                        ->get()
                                        ->filter(fn($f) => $f->getSaldoConNotas() > 0)
                                        ->mapWithKeys(fn($f) => [
                                            $f->cod_factura => "{$f->numero_factura} (Saldo: Gs. " . number_format($f->getSaldoConNotas(), 0, ',', '.') . ")"
                                        ])
                                        ->toArray();
                                })
                                ->live()
                                ->bulkToggleable()
                                ->helperText('Seleccione las facturas que desea abonar.'),
                        ])
                        ->extraAttributes(['style' => 'max-height: 400px; overflow-y: auto;'])
                        ->columnSpan(2),
                ])->columnSpanFull(),

                // --- FILA 2: Formas de Pago (Compacta / POS Style) ---
                Section::make('Formas de Pago')
                    ->schema([
                        Repeater::make('formas_pago')
                            ->hiddenLabel()
                            ->schema([
                                Select::make('tipo_transaccion')
                                    ->label('Tipo')
                                    ->options([
                                        'Efectivo' => 'Efectivo',
                                        'Tarjeta Crédito' => 'Tarjeta Crédito',
                                        'Tarjeta Débito' => 'Tarjeta Débito',
                                        'Transferencia' => 'Transferencia',
                                        'Cheque' => 'Cheque',
                                    ])
                                    ->required()
                                    ->live()
                                    ->columnSpan(1),

                                TextInput::make('monto')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Gs.')
                                    ->live(onBlur: true)
                                    ->columnSpan(1),

                                Select::make('banco_id')
                                    ->label('Entidad')
                                    ->options(fn () => EntidadBancaria::activas()->pluck('nombre', 'cod_entidad_bancaria')->toArray())
                                    ->visible(fn (Get $get) => $get('tipo_transaccion') !== 'Efectivo')
                                    ->columnSpan(1),

                                TextInput::make('referencia')
                                    ->label('Nº Ref/Voucher')
                                    ->visible(fn (Get $get) => $get('tipo_transaccion') !== 'Efectivo')
                                    ->columnSpan(1),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->addActionLabel('Agregar forma de pago')
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // --- FILA 3: Resumen Financiero Horizontal ---
                Section::make('Resumen Financiero')
                    ->columns(3)
                    ->schema([
                        Placeholder::make('total_a_pagar')
                            ->label('Total a Pagar')
                            ->content(function (callable $get) {
                                $seleccionadas = $get('facturas_seleccionadas') ?? [];
                                if (empty($seleccionadas)) return 'Gs. 0';
                                
                                $total = Factura::whereIn('cod_factura', $seleccionadas)
                                    ->get()
                                    ->sum(fn($f) => $f->getSaldoConNotas());
                                    
                                return 'Gs. ' . number_format($total, 0, ',', '.');
                            }),
                        Placeholder::make('total_recibido')
                            ->label('Total Recibido')
                            ->content(function (callable $get) {
                                $pagos = $get('formas_pago') ?? [];
                                $total = collect($pagos)->sum(fn($p) => (float) ($p['monto'] ?? 0));
                                return 'Gs. ' . number_format($total, 0, ',', '.');
                            }),
                        Placeholder::make('vuelto')
                            ->label('Vuelto / Saldo')
                            ->content(function (Get $get) {
                                $seleccionadas = $get('facturas_seleccionadas') ?? [];
                                $pagos = $get('formas_pago') ?? [];
                                
                                $totalPagar = 0;
                                if (!empty($seleccionadas)) {
                                    $totalPagar = (float) Factura::whereIn('cod_factura', $seleccionadas)->get()->sum(fn($f) => $f->getSaldoConNotas());
                                }
                                
                                $totalRecibido = collect($pagos)->sum(fn($p) => (float) ($p['monto'] ?? 0));

                                $colorClass = '';
                                $textoEstado = '';
                                $montoMostrar = 0;

                                if ($totalPagar == 0 && $totalRecibido == 0) {
                                    $colorClass = 'text-gray-500 font-bold text-xl';
                                    $textoEstado = '-';
                                    $montoMostrar = 0;
                                } elseif ($totalRecibido == $totalPagar) {
                                    $colorClass = 'text-success-600 font-bold text-xl';
                                    $textoEstado = 'Cobro Exacto';
                                    $montoMostrar = 0;
                                } elseif ($totalRecibido > $totalPagar) {
                                    $colorClass = 'text-warning-600 font-bold text-xl';
                                    $textoEstado = 'Vuelto para el cliente';
                                    $montoMostrar = $totalRecibido - $totalPagar;
                                } else {
                                    $colorClass = 'text-danger-600 font-bold text-xl';
                                    $textoEstado = 'Falta cobrar (Saldo Pendiente)';
                                    $montoMostrar = $totalPagar - $totalRecibido;
                                }

                                $montoFormateado = number_format($montoMostrar, 0, ',', '.');
                                $html = "<span class='{$colorClass}'>Gs. {$montoFormateado} ({$textoEstado})</span>";
                                
                                return new HtmlString($html);
                            }),
                    ])->columnSpanFull(),
            ])
            ->statePath('data')
            ->columns(1);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('guardar')
                ->label('Procesar Cobro')
                ->color('success')
                ->submit('guardarCobro'),
            Action::make('cancelar')
                ->label('Cancelar')
                ->color('gray')
                ->url(route('filament.admin.pages.dashboard')),
        ];
    }

    public function guardarCobro(): void
    {
        $data = $this->form->getState();
        $this->procesarCobro($data);
    }

    public function procesarCobro(array $data): void
    {
        DB::transaction(function () use ($data) {
            $apertura = \App\Models\AperturaCaja::where('usuario', auth()->user()->name)
                ->where('estado', 'Abierta')
                ->first();

            $cobro = \App\Models\Cobro::create([
                'cod_cliente' => $data['cliente_id'],
                'cod_apertura' => $apertura?->cod_apertura,
                'fecha_cobro' => now(),
                'monto_total' => collect($data['formas_pago'])->sum(fn($p) => (float) ($p['monto'] ?? 0)),
                'observaciones' => $data['observaciones'] ?? null,
                'usuario_alta' => auth()->user()->name,
                'fecha_alta' => now(),
            ]);

            foreach ($data['facturas_seleccionadas'] as $codFactura) {
                $factura = Factura::find($codFactura);
                if ($factura) {
                    $cobro->detalles()->create([
                        'cod_factura' => $codFactura,
                        'numero_cuota' => 1,
                        'monto_cuota' => $factura->getSaldoConNotas(),
                    ]);
                }
            }

            foreach ($data['formas_pago'] as $formaPago) {
                $cobro->formasPago()->create([
                    'tipo_transaccion' => $formaPago['tipo_transaccion'],
                    'monto' => $formaPago['monto'],
                    'cod_entidad_bancaria' => $formaPago['banco_id'] ?? null,
                    'numero_voucher' => $formaPago['referencia'] ?? null,
                    'numero_cheque' => null,
                ]);
            }
        });

        Notification::make()
            ->success()
            ->title('Cobro procesado')
            ->body('El cobro se ha registrado exitosamente.')
            ->send();

        $this->form->fill();
    }
}
