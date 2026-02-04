<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotaResource\Pages;
use App\Models\Nota;
use App\Models\Factura;
use App\Models\Timbrado;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Forms\Set;

class NotaResource extends Resource
{
    protected static ?string $model = Nota::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Notas Crédito/Débito';

    protected static ?string $modelLabel = 'Nota';

    protected static ?string $pluralModelLabel = 'Notas de Crédito/Débito';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Tipo de Nota')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('tipo_nota')
                                    ->label('Tipo')
                                    ->options([
                                        'credito' => 'Nota de Crédito (Reduce el monto)',
                                        'debito' => 'Nota de Débito (Aumenta el monto)',
                                    ])
                                    ->required()
                                    ->live()
                                    ->helperText(fn (Get $get) => match($get('tipo_nota')) {
                                        'credito' => '💡 Nota de Crédito: Para devoluciones, descuentos o errores de cobro',
                                        'debito' => '💡 Nota de Débito: Para intereses, gastos adicionales o ajustes',
                                        default => 'Seleccione el tipo de nota a emitir'
                                    }),

                                Forms\Components\Select::make('tipo_operacion')
                                    ->label('Tipo de Operación')
                                    ->options([
                                        'anulacion' => '🚫 Anulación Total (Carga todos los ítems)',
                                        'devolucion' => '↩️ Devolución Parcial (Selecciona ítems)',
                                        'otros' => '✏️ Otros (Manual)',
                                    ])
                                    ->required()
                                    ->live()
                                    ->visible(fn (Get $get) => $get('tipo_nota') === 'credito')
                                    ->helperText('Seleccione el motivo de la nota de crédito'),
                            ]),
                    ])
                    ->columns(1)
                    ->collapsible(),

                Section::make('Factura de Referencia')
                    ->schema([
                        Forms\Components\Select::make('cod_factura')
                            ->label('Factura')
                            ->options(function () {
                                return Factura::where('estado', 'Emitida')
                                    ->where('condicion_venta', 'Crédito')
                                    ->with('cliente')
                                    ->get()
                                    ->mapWithKeys(function ($factura) {
                                        $saldo = $factura->getSaldoConNotas();
                                        return [
                                            $factura->cod_factura =>
                                            $factura->numero_factura . ' - ' .
                                            $factura->cliente->nombre_completo . ' - ' .
                                            'Saldo: ' . number_format($saldo, 0, ',', '.') . ' Gs'
                                        ];
                                    });
                            })
                            ->searchable()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                if ($state) {
                                    $factura = Factura::with(['detalles.articulo', 'cliente'])->find($state);
                                    if ($factura) {
                                        $set('factura_info_numero', $factura->numero_factura);
                                        $set('factura_info_fecha', $factura->fecha_factura->format('d/m/Y'));
                                        $set('factura_info_total', number_format($factura->total_general, 0, ',', '.') . ' Gs');
                                        $set('factura_info_saldo', number_format($factura->getSaldoConNotas(), 0, ',', '.') . ' Gs');
                                        $set('factura_info_cliente', $factura->cliente->nombre_completo);

                                        // Cargar ítems automáticamente según tipo de operación
                                        $tipoOperacion = $get('tipo_operacion');
                                        $tipoNota = $get('tipo_nota');

                                        if ($tipoNota === 'credito' && in_array($tipoOperacion, ['anulacion', 'devolucion'])) {
                                            $detalles = [];
                                            foreach ($factura->detalles as $detalle) {
                                                // Obtener descripción del artículo o usar la del detalle
                                                $descripcion = $detalle->descripcion;
                                                if ($detalle->articulo) {
                                                    $descripcion = $detalle->articulo->descripcion ?? $descripcion;
                                                }

                                                $detalles[] = [
                                                    'cod_factura_detalle' => $detalle->cod_detalle,
                                                    'descripcion' => $descripcion,
                                                    'cantidad' => $detalle->cantidad,
                                                    'precio_unitario' => $detalle->precio_unitario,
                                                    'tipo_iva' => match($detalle->tipo_iva) {
                                                        '10' => '10%',
                                                        '5' => '5%',
                                                        'Exenta' => 'Exenta',
                                                        default => '10%'
                                                    },
                                                    'seleccionado' => $tipoOperacion === 'anulacion' ? true : false,
                                                ];
                                            }
                                            $set('detalles', $detalles);
                                            self::calcularTotalesGenerales($set, $get);
                                        }
                                    }
                                }
                            }),

                        Grid::make(3)
                            ->schema([
                                Placeholder::make('factura_info_numero')
                                    ->label('Número')
                                    ->content(fn (Get $get) => $get('factura_info_numero') ?? '-'),

                                Placeholder::make('factura_info_fecha')
                                    ->label('Fecha')
                                    ->content(fn (Get $get) => $get('factura_info_fecha') ?? '-'),

                                Placeholder::make('factura_info_cliente')
                                    ->label('Cliente')
                                    ->content(fn (Get $get) => $get('factura_info_cliente') ?? '-'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                Placeholder::make('factura_info_total')
                                    ->label('Total Factura')
                                    ->content(fn (Get $get) => $get('factura_info_total') ?? '-'),

                                Placeholder::make('factura_info_saldo')
                                    ->label('Saldo Actual')
                                    ->content(fn (Get $get) => $get('factura_info_saldo') ?? '-')
                                    ->extraAttributes(['class' => 'font-bold text-lg']),
                            ]),
                    ])
                    ->columns(1)
                    ->visible(fn (Get $get) => filled($get('tipo_nota'))),

                Section::make('Datos de la Nota')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('cod_timbrado')
                                    ->label('Timbrado')
                                    ->options(function () {
                                        return Timbrado::where('activo', true)
                                            ->where('fecha_inicio_vigencia', '<=', now())
                                            ->where('fecha_fin_vigencia', '>=', now())
                                            ->get()
                                            ->mapWithKeys(fn ($t) => [
                                                $t->cod_timbrado => $t->numero_timbrado .
                                                    ' (' . $t->establecimiento . '-' . $t->punto_expedicion . ')' .
                                                    ' - Vence: ' . $t->fecha_fin_vigencia->format('d/m/Y')
                                            ]);
                                    })
                                    ->required()
                                    ->searchable()
                                    ->helperText('Seleccione el timbrado apropiado para la nota'),

                                Forms\Components\TextInput::make('numero_nota')
                                    ->label('Número de Nota')
                                    ->required()
                                    ->maxLength(20)
                                    ->placeholder('001-001-0000123')
                                    ->helperText('Formato: 001-001-0000123'),

                                Forms\Components\DatePicker::make('fecha_emision')
                                    ->label('Fecha de Emisión')
                                    ->required()
                                    ->default(now())
                                    ->maxDate(now())
                                    ->native(false),
                            ]),

                        Forms\Components\Textarea::make('motivo')
                            ->label('Motivo')
                            ->required()
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText(fn (Get $get) => match($get('tipo_nota')) {
                                'credito' => 'Ej: Devolución de mercadería, Descuento por promoción, Error en facturación',
                                'debito' => 'Ej: Intereses por mora, Gastos de envío adicionales, Ajuste de precio',
                                default => 'Describa el motivo de la nota'
                            }),
                    ])
                    ->columns(1)
                    ->visible(fn (Get $get) => filled($get('cod_factura'))),

                Section::make('Detalles de la Nota')
                    ->description(fn (Get $get) => match($get('tipo_operacion')) {
                        'anulacion' => '📋 Anulación: Todos los ítems de la factura están incluidos',
                        'devolucion' => '✅ Devolución: Marque los ítems que se están devolviendo y ajuste cantidades si es necesario',
                        'otros' => '➕ Agregue manualmente los conceptos de la nota',
                        default => null
                    })
                    ->schema([
                        Repeater::make('detalles')
                            ->label('Items')
                            ->schema([
                                Forms\Components\Checkbox::make('seleccionado')
                                    ->label('✓')
                                    ->live()
                                    ->visible(fn (Get $get) => $get('../../tipo_operacion') === 'devolucion')
                                    ->afterStateUpdated(fn (Set $set, Get $get) =>
                                        self::calcularTotalesGenerales($set, $get)
                                    )
                                    ->columnSpan(1),

                                Forms\Components\Hidden::make('cod_factura_detalle'),

                                Forms\Components\TextInput::make('descripcion')
                                    ->label('Descripción')
                                    ->required()
                                    ->maxLength(255)
                                    ->disabled(fn (Get $get) => in_array($get('../../tipo_operacion'), ['anulacion', 'devolucion']))
                                    ->columnSpan(fn (Get $get) => $get('../../tipo_operacion') === 'devolucion' ? 2 : 2),

                                Forms\Components\TextInput::make('cantidad')
                                    ->label('Cantidad')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(0.01)
                                    ->disabled(fn (Get $get) => $get('../../tipo_operacion') === 'anulacion')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, Get $get) =>
                                        self::calcularTotalDetalle($set, $get)
                                    ),

                                Forms\Components\TextInput::make('precio_unitario')
                                    ->label('Precio Unitario')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Gs')
                                    ->disabled(fn (Get $get) => in_array($get('../../tipo_operacion'), ['anulacion', 'devolucion']))
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, Get $get) =>
                                        self::calcularTotalDetalle($set, $get)
                                    ),

                                Forms\Components\Select::make('tipo_iva')
                                    ->label('IVA')
                                    ->options([
                                        'Exenta' => 'Exenta',
                                        '5%' => '5%',
                                        '10%' => '10%',
                                    ])
                                    ->required()
                                    ->default('10%')
                                    ->disabled(fn (Get $get) => in_array($get('../../tipo_operacion'), ['anulacion', 'devolucion']))
                                    ->live()
                                    ->afterStateUpdated(fn (Set $set, Get $get) =>
                                        self::calcularTotalDetalle($set, $get)
                                    ),

                                Placeholder::make('total_item')
                                    ->label('Total')
                                    ->content(function (Get $get) {
                                        $cantidad = floatval($get('cantidad') ?? 0);
                                        $precio = floatval($get('precio_unitario') ?? 0);
                                        $tipoIva = $get('tipo_iva');

                                        $subtotal = $cantidad * $precio;
                                        $iva = match($tipoIva) {
                                            '10%' => $subtotal * 0.10,
                                            '5%' => $subtotal * 0.05,
                                            default => 0
                                        };
                                        $total = $subtotal + $iva;

                                        return number_format($total, 0, ',', '.') . ' Gs';
                                    }),
                            ])
                            ->columns(7)
                            ->defaultItems(fn (Get $get) => in_array($get('tipo_operacion'), ['anulacion', 'devolucion']) ? 0 : 1)
                            ->addActionLabel('Agregar ítem')
                            ->addable(fn (Get $get) => !in_array($get('tipo_operacion'), ['anulacion', 'devolucion']))
                            ->deletable(fn (Get $get) => !in_array($get('tipo_operacion'), ['anulacion', 'devolucion']))
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                                ($state['descripcion'] ?? 'Nuevo ítem') .
                                (isset($state['seleccionado']) && $state['seleccionado'] === false ? ' ⚠️ NO incluido' : '')
                            )
                            ->live()
                            ->afterStateUpdated(fn (Set $set, Get $get) =>
                                self::calcularTotalesGenerales($set, $get)
                            )
                            ->deleteAction(
                                fn (Forms\Components\Actions\Action $action) => $action->after(fn (Set $set, Get $get) =>
                                    self::calcularTotalesGenerales($set, $get)
                                )
                            ),

                        Grid::make(3)
                            ->schema([
                                Placeholder::make('total_gravado_10')
                                    ->label('Subtotal Gravado 10%')
                                    ->content(fn (Get $get) =>
                                        number_format($get('subtotal_gravado_10') ?? 0, 0, ',', '.') . ' Gs'
                                    ),

                                Placeholder::make('total_iva_10_display')
                                    ->label('IVA 10%')
                                    ->content(fn (Get $get) =>
                                        number_format($get('total_iva_10') ?? 0, 0, ',', '.') . ' Gs'
                                    ),

                                Placeholder::make('total_gravado_5')
                                    ->label('Subtotal Gravado 5%')
                                    ->content(fn (Get $get) =>
                                        number_format($get('subtotal_gravado_5') ?? 0, 0, ',', '.') . ' Gs'
                                    ),

                                Placeholder::make('total_iva_5_display')
                                    ->label('IVA 5%')
                                    ->content(fn (Get $get) =>
                                        number_format($get('total_iva_5') ?? 0, 0, ',', '.') . ' Gs'
                                    ),

                                Placeholder::make('total_exenta_display')
                                    ->label('Exenta')
                                    ->content(fn (Get $get) =>
                                        number_format($get('subtotal_exenta') ?? 0, 0, ',', '.') . ' Gs'
                                    ),

                                Placeholder::make('monto_total_display')
                                    ->label('TOTAL DE LA NOTA')
                                    ->content(fn (Get $get) =>
                                        number_format($get('monto_total') ?? 0, 0, ',', '.') . ' Gs'
                                    )
                                    ->extraAttributes(['class' => 'font-bold text-xl text-primary-600']),
                            ]),

                        Forms\Components\Hidden::make('subtotal_gravado_10')->default(0),
                        Forms\Components\Hidden::make('subtotal_gravado_5')->default(0),
                        Forms\Components\Hidden::make('subtotal_exenta')->default(0),
                        Forms\Components\Hidden::make('total_iva_10')->default(0),
                        Forms\Components\Hidden::make('total_iva_5')->default(0),
                        Forms\Components\Hidden::make('monto_total')->default(0),
                    ])
                    ->columns(1)
                    ->visible(fn (Get $get) => filled($get('motivo'))),

                Section::make('Observaciones Adicionales')
                    ->schema([
                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(2)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->visible(fn (Get $get) => filled($get('motivo'))),
            ]);
    }

    protected static function calcularTotalDetalle(Set $set, Get $get): void
    {
        $cantidad = floatval($get('cantidad') ?? 0);
        $precio = floatval($get('precio_unitario') ?? 0);
        $tipoIva = $get('tipo_iva');

        $subtotal = $cantidad * $precio;
        $iva = match($tipoIva) {
            '10%' => $subtotal * 0.10,
            '5%' => $subtotal * 0.05,
            default => 0
        };

        $set('subtotal', $subtotal);
        $set('monto_iva', $iva);
        $set('total', $subtotal + $iva);
    }

    protected static function calcularTotalesGenerales(Set $set, Get $get): void
    {
        $detalles = $get('detalles') ?? [];
        $tipoOperacion = $get('tipo_operacion');

        $totales = [
            'subtotal_gravado_10' => 0,
            'subtotal_gravado_5' => 0,
            'subtotal_exenta' => 0,
            'total_iva_10' => 0,
            'total_iva_5' => 0,
        ];

        foreach ($detalles as $detalle) {
            // En devoluciones, solo contar los ítems seleccionados
            if ($tipoOperacion === 'devolucion' && !($detalle['seleccionado'] ?? false)) {
                continue;
            }

            $cantidad = floatval($detalle['cantidad'] ?? 0);
            $precio = floatval($detalle['precio_unitario'] ?? 0);
            $tipoIva = $detalle['tipo_iva'] ?? 'Exenta';

            $subtotal = $cantidad * $precio;

            match($tipoIva) {
                '10%' => [
                    $totales['subtotal_gravado_10'] += $subtotal,
                    $totales['total_iva_10'] += $subtotal * 0.10,
                ],
                '5%' => [
                    $totales['subtotal_gravado_5'] += $subtotal,
                    $totales['total_iva_5'] += $subtotal * 0.05,
                ],
                'Exenta' => $totales['subtotal_exenta'] += $subtotal,
                default => null
            };
        }

        $montoTotal = $totales['subtotal_gravado_10'] + $totales['total_iva_10'] +
                      $totales['subtotal_gravado_5'] + $totales['total_iva_5'] +
                      $totales['subtotal_exenta'];

        $set('subtotal_gravado_10', $totales['subtotal_gravado_10']);
        $set('subtotal_gravado_5', $totales['subtotal_gravado_5']);
        $set('subtotal_exenta', $totales['subtotal_exenta']);
        $set('total_iva_10', $totales['total_iva_10']);
        $set('total_iva_5', $totales['total_iva_5']);
        $set('monto_total', $montoTotal);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_nota')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('tipo_nota')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'credito' => 'success',
                        'debito' => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'credito' => 'Crédito',
                        'debito' => 'Débito',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('factura.numero_factura')
                    ->label('Factura')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('factura.cliente.nombre_completo')
                    ->label('Cliente')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('fecha_emision')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('monto_total')
                    ->label('Monto')
                    ->money('PYG', locale: 'es_PY')
                    ->sortable(),

                Tables\Columns\TextColumn::make('motivo')
                    ->label('Motivo')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->motivo),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Emitida' => 'success',
                        'Anulada' => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_nota')
                    ->label('Tipo')
                    ->options([
                        'credito' => 'Nota de Crédito',
                        'debito' => 'Nota de Débito',
                    ]),

                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Emitida' => 'Emitida',
                        'Anulada' => 'Anulada',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Nota $record) => $record->puedeAnularse())
                    ->form([
                        Forms\Components\Textarea::make('motivo_anulacion')
                            ->label('Motivo de Anulación')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Nota $record, array $data) {
                        $record->anular($data['motivo_anulacion']);
                    })
                    ->successNotificationTitle('Nota anulada correctamente'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotas::route('/'),
            'create' => Pages\CreateNota::route('/create'),
            'view' => Pages\ViewNota::route('/{record}'),
            'edit' => Pages\EditNota::route('/{record}/edit'),
        ];
    }
}
