<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CobroResource\Pages;
use App\Models\Cobro;
use App\Models\Factura;
use App\Models\EntidadBancaria;
use App\Models\Personas;
use App\Models\AperturaCaja;
use App\Forms\Components\FacturasSelector;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Auth;

class CobroResource extends Resource
{
    protected static ?string $model = Cobro::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Cobros';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Header: Cliente y Fecha
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Select::make('cod_cliente')
                                ->label('Cliente')
                                ->options(function () {
                                    return Personas::whereHas('facturas', function ($query) {
                                        $query->where('condicion_venta', 'Crédito')
                                              ->where('estado', 'Emitida');
                                    })
                                    ->get()
                                    ->mapWithKeys(fn ($c) => [$c->cod_persona => $c->nombre_completo]);
                                })
                                ->searchable()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (callable $set) {
                                    $set('detalles', []);
                                })
                                ->columnSpan(2)
                                ->prefixIcon('heroicon-o-user'),

                            Forms\Components\DatePicker::make('fecha_cobro')
                                ->label('Fecha')
                                ->default(now())
                                ->required()
                                ->maxDate(now())
                                ->native(false)
                                ->prefixIcon('heroicon-o-calendar'),
                        ]),
                    ])
                    ->compact(),

                // Grid principal: Facturas (70%) | Resumen (30%)
                Forms\Components\Grid::make(10)->schema([
                // Facturas a Cobrar
                Forms\Components\Section::make('Facturas a Cobrar')
                    ->columnSpan(7)
                    ->schema([
                        FacturasSelector::make('detalles')
                            ->label(''),
                    ])
                    ->headerActions([
                        Forms\Components\Actions\Action::make('total_facturas')
                            ->label(fn (callable $get) => 'Total: Gs. ' . number_format(collect($get('detalles') ?? [])->sum('monto_cuota'), 0, ',', '.'))
                            ->color('success')
                            ->disabled(),
                    ]),

                    // Resumen del Cobro
                    Forms\Components\Section::make('Resumen')
                        ->columnSpan(3)
                        ->schema([
                            Forms\Components\Placeholder::make('total_cobrar')
                                ->label('Total a Cobrar')
                                ->content(function (Get $get) {
                                    $total = collect($get('detalles') ?? [])->sum('monto_cuota');
                                    return new \Illuminate\Support\HtmlString('<div class="text-2xl font-bold text-success-600">Gs. ' . number_format($total, 0, ',', '.') . '</div>');
                                }),

                            Forms\Components\Placeholder::make('total_recibido')
                                ->label('Total Recibido')
                                ->content(function (Get $get) {
                                    $total = collect($get('formas_pago') ?? [])->sum('monto');
                                    return new \Illuminate\Support\HtmlString('<div class="text-2xl font-bold text-primary-600">Gs. ' . number_format($total, 0, ',', '.') . '</div>');
                                }),

                            Forms\Components\Placeholder::make('diferencia')
                                ->label('Diferencia')
                                ->content(function (Get $get) {
                                    $cobrar = collect($get('detalles') ?? [])->sum('monto_cuota');
                                    $recibido = collect($get('formas_pago') ?? [])->sum('monto');
                                    $diff = $recibido - $cobrar;
                                    
                                    if ($diff == 0) {
                                        return new \Illuminate\Support\HtmlString('<div class="text-xl font-bold text-gray-600">Gs. 0</div>');
                                    } elseif ($diff > 0) {
                                        return new \Illuminate\Support\HtmlString('<div class="text-xl font-bold text-warning-600">+ Gs. ' . number_format($diff, 0, ',', '.') . '</div>');
                                    } else {
                                        return new \Illuminate\Support\HtmlString('<div class="text-xl font-bold text-danger-600">- Gs. ' . number_format(abs($diff), 0, ',', '.') . '</div>');
                                    }
                                }),
                        ])
                        ->compact(),
                ]),

                // Formas de Pago
                Forms\Components\Section::make('Formas de Pago')
                    ->schema([
                        Forms\Components\Repeater::make('formas_pago')
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('tipo_transaccion')
                                    ->label('Método')
                                    ->options([
                                        'efectivo' => '💵 Efectivo',
                                        'tarjeta_credito' => '💳 Tarjeta Crédito',
                                        'tarjeta_debito' => '💳 Tarjeta Débito',
                                        'transferencia' => '🏦 Transferencia',
                                    ])
                                    ->required()
                                    ->live()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('monto')
                                    ->label('Monto')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Gs.')
                                    ->live()
                                    ->columnSpan(2),

                                Forms\Components\Select::make('cod_entidad_bancaria')
                                    ->label('Banco')
                                    ->options(EntidadBancaria::activas()->pluck('nombre', 'cod_entidad_bancaria'))
                                    ->searchable()
                                    ->visible(fn (Get $get): bool => in_array($get('tipo_transaccion'), ['tarjeta_credito', 'tarjeta_debito', 'transferencia']))
                                    ->columnSpan(2),
                            ])
                            ->columns(6)
                            ->defaultItems(1)
                            ->addActionLabel('+ Agregar Pago')
                            ->reorderable(false),
                    ])
                    ->compact(),

                // Observaciones
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('Notas adicionales (opcional)'),
                    ])
                    ->compact(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_cobro')->label('N°')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('fecha_cobro')->label('Fecha')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('cliente.nombre_completo')->label('Cliente')->searchable()->limit(30),
                Tables\Columns\TextColumn::make('monto_total')->label('Monto')->money('PYG', divideBy: 1)->sortable(),
                Tables\Columns\TextColumn::make('usuario_alta')->label('Usuario'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('fecha_cobro', 'desc');
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCobros::route('/'),
            'create' => Pages\CreateCobro::route('/create'),
            'view' => Pages\ViewCobro::route('/{record}'),
        ];
    }
}
