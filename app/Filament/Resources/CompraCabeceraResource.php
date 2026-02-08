<?php

namespace App\Filament\Resources;

use Closure;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use App\Models\Articulos;
use Filament\Tables\Table;
//alex
use Ramsey\Collection\Set;
use App\Models\CompraCabecera;
use App\Models\OrdenCompraCabecera;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CompraCabeceraResource\Pages;
use App\Filament\Resources\CompraCabeceraResource\RelationManagers;
use App\Filament\Resources\CompraCabeceraResource\RelationManagers\CompraDetalleRelationManager;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\RepeatableEntry;

class CompraCabeceraResource extends Resource
{
    protected static ?string $model = CompraCabecera::class;

     protected  static bool $canCreateAnother =  false;


    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Compras'; // <-- Asegúrate de que tenga un icono
    protected static ?string $navigationLabel = 'Facturas de Compra'; // <-- Opcional, pero útil
    protected static ?string $modelLabel = 'Factura de Compra';

    protected static ?int $navigationSort = 4;

    // Cargar relaciones necesarias para el cálculo del estado de recepción
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('detalles');
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECCIÓN 1: Información del Comprobante
                Section::make('Información del Comprobante')
                    ->description('Datos principales de la factura de compra')
                    ->collapsible()
                    ->columns(3)
                    ->schema([
                        // Tipo de Comprobante
                        Select::make('tip_comprobante')
                            ->label('Tipo Factura')
                            ->options([
                                'FAC' => 'Factura Crédito',
                                'CON' => 'Factura Contado',
                            ])
                            ->required()
                            ->default('FAC')
                            ->native(false)
                            ->columnSpan(1),

                        // Proveedor
                        Select::make('cod_proveedor')
                            ->label('Proveedor')
                            ->relationship('proveedor','id')
                            ->getOptionLabelFromRecordUsing(fn ($record) =>
                                $record?->personas_pro?->nombres ?? $record?->nombres ?? ''
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('Seleccione el proveedor de la factura')
                            ->columnSpan(2),

                        // Sucursal
                        Select::make('cod_sucursal')
                            ->label('Sucursal')
                            ->relationship('sucursal', 'descripcion')
                            ->default(1)
                            ->required()
                            ->disabled(fn ($context) => $context === 'edit')
                            ->dehydrated()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1),

                        // Usuario Alta
                        TextInput::make('usuario_alta')
                            ->label('Usuario')
                            ->default(fn () => auth()->user()->name ?? 'Sistema')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),

                        // Fecha Alta
                        TextInput::make('fecha_alta')
                            ->label('Fecha Alta')
                            ->default(Carbon::now()->toDateTimeString())
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),

                        // Fecha Comprobante
                        DatePicker::make('fec_comprobante')
                            ->label('Fecha Factura')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->columnSpan(1),

                        // Fecha Vencimiento
                        DatePicker::make('fec_vencimiento')
                            ->label('Vencimiento')
                            ->required()
                            ->default(now()->addDays(30))
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->helperText('Fecha de vencimiento de pago')
                            ->columnSpan(1),

                        // Condición de Compra
                        Select::make('cod_condicion_compra')
                            ->label('Condición de Compra')
                            ->relationship('condicionCompra', 'descripcion')
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                $cuotasInfo = $record->cant_cuota > 0
                                    ? " ({$record->cant_cuota} cuotas)"
                                    : ' (Contado)';
                                return $record->descripcion . $cuotasInfo;
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(1),
                    ]),

                // SECCIÓN 2: Timbrado y Numeración
                Section::make('Timbrado y Numeración')
                    ->description('Ingrese los datos del comprobante físico')
                    ->collapsible()
                    ->columns(3)
                    ->schema([
                        // Serie Comprobante
                        TextInput::make('ser_comprobante')
                            ->label('Serie')
                            ->required()
                            ->maxLength(7)
                            ->placeholder('001-003')
                            ->reactive()
                            ->rules(['regex:/^\d{3}-\d{3}$/'])
                            ->helperText('Formato: 001-003')
                          /*  ->afterStateUpdated(function ($state, callable $set, $get) {
                                if (preg_match('/^\d{3}-\d{3}$/', $state) && $get('cod_proveedor')) {
                                    $timbrado = DB::table('cm_timbrado_prov')
                                        ->where('cod_proveedor', $get('cod_proveedor'))
                                        ->where('ser_timbrado', $state)
                                        ->value('num_timbrado');

                                    if ($timbrado) {
                                        $set('timbrado', $timbrado);
                                        Notification::make()
                                            ->title('✅ Timbrado encontrado')
                                            ->success()
                                            ->send();
                                    } else {
                                        $set('timbrado', null);
                                        Notification::make()
                                            ->title('⚠️ Timbrado no encontrado')
                                            ->body("No se encontró el timbrado para la serie **{$state}**")
                                            ->warning()
                                            ->send();
                                    }
                                } else {
                                    $set('timbrado', null);
                                }
                            })*/
                            ->columnSpan(1),

                        // Número Comprobante
                        TextInput::make('nro_comprobante')
                            ->label('Número')
                            ->required()
                            ->maxLength(7)
                            ->numeric()
                            ->helperText('Número de la factura')
                            ->rules([
                                fn (callable $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    $existe = DB::table('cm_compras_cabecera')
                                        ->where('cod_proveedor', $get('cod_proveedor'))
                                        ->where('tip_comprobante', $get('tip_comprobante'))
                                        ->where('ser_comprobante', $get('ser_comprobante'))
                                        ->where('nro_comprobante', $value)
                                        ->when($get('id_compra_cabecera'), fn ($query, $id) => $query->where('id_compra_cabecera', '!=', $id))
                                        ->exists();

                                    if ($existe) {
                                        $fail("⚠️ La factura **{$get('ser_comprobante')}-{$value}** ya existe en el sistema");
                                    }
                                },
                            ])
                            ->columnSpan(1),

                        // Timbrado
                        TextInput::make('timbrado')
                            ->label('Timbrado')
                            ->required()
                            ->numeric()
                            ->maxLength(7)
                            ->validationMessages([
                                'max' => 'El timbrado no debe tener más de 7 dígitos.',
                            ])
                            ->rules(['regex:/^\d{1,7}$/'])
                            ->helperText('Ingrese hasta 7 dígitos numéricos')
                            ->columnSpan(1),

                        // Nro OC Referencia
                        Select::make('nro_oc_ref')
                            ->label('Nro. OC Referencia')
                            ->options(function (Get $get) {
                                $codProveedor = $get('cod_proveedor');

                                // Si no hay proveedor seleccionado, no mostrar OC
                                if (!$codProveedor) {
                                    return [];
                                }

                                // Solo órdenes de compra APROBADAS (estado 2) del proveedor seleccionado
                                return OrdenCompraCabecera::where('estado', 'APROBADO')
                                    ->where('cod_proveedor', $codProveedor)
                                    ->get()
                                    ->mapWithKeys(function ($orden) {
                                        $label = 'OC Nro. ' . $orden->nro_orden_compra;
                                        if ($orden->fec_orden) {
                                            $fecha = $orden->fec_orden instanceof \Carbon\Carbon
                                                ? $orden->fec_orden->format('d/m/Y')
                                                : $orden->fec_orden;
                                            $label .= ' — ' . $fecha;
                                        }
                                        return [$orden->nro_orden_compra => $label];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                if (!$state) {
                                    $set('detalles', []);
                                    return;
                                }

                                // Cargar la OC con sus detalles
                                $ordenCompra = OrdenCompraCabecera::with('ordenCompraDetalles.articulo')
                                    ->find($state);

                                if (!$ordenCompra) {
                                    return;
                                }

                                // Mapear los detalles de la OC
                                $detalles = $ordenCompra->ordenCompraDetalles->map(function ($detalle) {
                                    $cantidad = (float) $detalle->cantidad;
                                    $precio = (float) $detalle->precio;
                                    $total = $cantidad * $precio;
                                    $iva = $total * 0.10;

                                    return [
                                        'cod_articulo' => $detalle->cod_articulo,
                                        'cantidad' => $cantidad,
                                        'precio_unitario' => $precio,
                                        'porcentaje_iva' => 10,
                                        'total_iva' => number_format($iva, 2, '.', ''),
                                        'monto_total_linea' => number_format($total, 2, '.', ''),
                                    ];
                                })->toArray();

                                $set('detalles', $detalles);

                                Notification::make()
                                    ->title('Detalles cargados desde OC')
                                    ->success()
                                    ->send();
                            })
                            ->placeholder('Seleccione una orden de compra')
                            ->helperText('Solo se muestran OC aprobadas del proveedor seleccionado')
                            ->columnSpan(1),

                        // Observación
                        TextInput::make('observacion')
                            ->label('Observaciones')
                            ->maxLength(255)
                            ->placeholder('Ingrese observaciones adicionales...')
                            ->columnSpan(2),
                    ]),

                // SECCIÓN 3: Detalle de Compra
                Section::make('Artículos y Detalles de la Compra')
                    ->description('Agregue los artículos comprados con sus cantidades y precios')
                    ->collapsed()
                    ->schema([
                        Repeater::make('detalles')
                            ->relationship()
                            ->schema([
                                // Artículo
                                Select::make('cod_articulo')
                                    ->label('Artículo')
                                    ->relationship('articulo', 'descripcion')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(3)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($state) {
                                            $articulo = Articulos::find($state);
                                            if ($articulo) {
                                                $precio = (float) $articulo->precio;
                                                $set('precio_unitario', $precio);
                                                $cantidad = 1;
                                                $total = $cantidad * $precio;
                                                $iva = max(0, $total) * 0.10;
                                                $set('monto_total_linea', number_format($total, 2, '.', ''));
                                                $set('total_iva', number_format($iva, 2, '.', ''));
                                            }
                                        }
                                    }),

                                // Cantidad
                                TextInput::make('cantidad')
                                    ->label('Cant.')
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->default(1)
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $cantidad = (float) $state;
                                        $precio = (float) $get('precio_unitario');
                                        $exenta = (float) ($get('exenta') ?? 0);
                                        $total = $cantidad * $precio;
                                        $iva = max(0, ($total - $exenta)) * 0.10;
                                        $set('monto_total_linea', number_format($total, 2, '.', ''));
                                        $set('total_iva', number_format($iva, 2, '.', ''));
                                    })
                                    ->columnSpan(1),

                                // Precio
                                TextInput::make('precio_unitario')
                                    ->label('Precio')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->prefix('₲')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $cantidad = (float) ($get('cantidad') ?? 0);
                                        $precio = (float) $state;
                                        $exenta = (float) ($get('exenta') ?? 0);
                                        $total = $cantidad * $precio;
                                        $iva = max(0, ($total - $exenta)) * 0.10;
                                        $set('monto_total_linea', number_format($total, 2, '.', ''));
                                        $set('total_iva', number_format($iva, 2, '.', ''));
                                    })
                                    ->columnSpan(1),

                                // % Impuesto
                                TextInput::make('porcentaje_iva')
                                    ->label('IVA %')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(10)
                                    ->suffix('%')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $cantidad = (float) ($get('cantidad') ?? 0);
                                        $precio = (float) ($get('precio_unitario') ?? 0);
                                        $porcentajeIva = (float) $state;
                                        $total = $cantidad * $precio;
                                        $iva = $total * ($porcentajeIva / 100);
                                        $set('monto_total_linea', number_format($total, 2, '.', ''));
                                        $set('total_iva', number_format($iva, 2, '.', ''));
                                    })
                                    ->columnSpan(1),

                                // Total Línea
                                TextInput::make('monto_total_linea')
                                    ->label('Total')
                                    ->numeric()
                                    ->readOnly()
                                    ->prefix('₲')
                                    ->dehydrated(true)
                                    ->extraAttributes(['class' => 'font-bold text-success-600'])
                                    ->columnSpan(1),
                            ])
                            ->columns(7)
                            ->addActionLabel('+ Artículo')
                            ->itemLabel(fn (array $state): ?string =>
                                $state['cod_articulo']
                                    ? Articulos::find($state['cod_articulo'])?->descripcion
                                    : null
                            )
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->live()
                            ->grid(1)
                    ]),

                // SECCIÓN 4: Totales de la Factura
                Section::make('Totales de la Factura')
                    ->description('Resumen de montos')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('total_gravada')
                                    ->label('Total Gravado')
                                    ->numeric()
                                    ->readOnly()
                                    ->prefix('₲')
                                    ->dehydrated(false)
                                    ->live()
                                    ->afterStateHydrated(function (TextInput $component, $state, $record) {
                                        if ($record && $record->detalles) {
                                            $total = $record->detalles->sum('monto_total_linea');
                                            $component->state(number_format($total, 0, '', ''));
                                        }
                                    })
                                    ->extraAttributes(['class' => 'font-bold text-lg']),

                                TextInput::make('tot_iva')
                                    ->label('Total IVA (10%)')
                                    ->numeric()
                                    ->readOnly()
                                    ->prefix('₲')
                                    ->dehydrated(false)
                                    ->live()
                                    ->afterStateHydrated(function (TextInput $component, $state, $record) {
                                        if ($record && $record->detalles) {
                                            $subtotal = $record->detalles->sum('monto_total_linea');
                                            $iva = $subtotal * 0.10;
                                            $component->state(number_format($iva, 0, '', ''));
                                        }
                                    })
                                    ->extraAttributes(['class' => 'font-bold text-lg text-warning-600']),

                                TextInput::make('total_general')
                                    ->label('Total General')
                                    ->numeric()
                                    ->readOnly()
                                    ->prefix('₲')
                                    ->dehydrated(false)
                                    ->live()
                                    ->afterStateHydrated(function (TextInput $component, $state, $record) {
                                        if ($record && $record->detalles) {
                                            $subtotal = $record->detalles->sum('monto_total_linea');
                                            $iva = $subtotal * 0.10;
                                            $total = $subtotal + $iva;
                                            $component->state(number_format($total, 0, '', ''));
                                        }
                                    })
                                    ->extraAttributes(['class' => 'font-bold text-xl text-success-600']),
                            ]),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Información del Comprobante')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('tip_comprobante')
                            ->label('Tipo Factura')
                            ->formatStateUsing(fn ($state) => match($state) {
                                'FAC' => 'Factura Crédito',
                                'CON' => 'Factura Contado',
                                default => $state
                            })
                            ->badge()
                            ->color(fn ($state) => match($state) {
                                'FAC' => 'warning',
                                'CON' => 'success',
                                default => 'gray'
                            }),

                        TextEntry::make('proveedor.personas_pro.nombre_completo')
                            ->label('Proveedor')
                            ->columnSpan(2),

                        TextEntry::make('sucursal.descripcion')
                            ->label('Sucursal'),

                        TextEntry::make('usuario_alta')
                            ->label('Usuario de Carga')
                            ->badge()
                            ->color('info'),

                        TextEntry::make('fec_alta')
                            ->label('Fecha Alta')
                            ->dateTime('d/m/Y H:i:s'),

                        TextEntry::make('fec_comprobante')
                            ->label('Fecha Factura')
                            ->date('d/m/Y'),

                        TextEntry::make('fec_vencimiento')
                            ->label('Vencimiento')
                            ->date('d/m/Y'),

                        TextEntry::make('condicionCompra.descripcion')
                            ->label('Condición de Compra')
                            ->badge()
                            ->color(fn ($state) => str_contains(strtolower($state), 'contado') ? 'success' : 'warning'),
                    ]),

                InfoSection::make('Timbrado y Numeración')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('ser_comprobante')
                            ->label('Serie')
                            ->badge(),

                        TextEntry::make('nro_comprobante')
                            ->label('Número')
                            ->badge()
                            ->color('primary'),

                        TextEntry::make('timbrado')
                            ->label('Timbrado')
                            ->badge()
                            ->color('success'),

                        TextEntry::make('nro_oc_ref')
                            ->label('OC Referencia')
                            ->formatStateUsing(fn ($state) => $state ? "OC Nro. {$state}" : 'Sin referencia')
                            ->badge()
                            ->color('gray'),

                        TextEntry::make('observacion')
                            ->label('Observaciones')
                            ->columnSpan(2)
                            ->placeholder('Sin observaciones'),
                    ]),

                InfoSection::make('Detalle de Artículos')
                    ->schema([
                        RepeatableEntry::make('detalles')
                            ->label('')
                            ->schema([
                                TextEntry::make('articulo.descripcion')
                                    ->label('Artículo')
                                    ->columnSpan(2),

                                TextEntry::make('cantidad')
                                    ->label('Cantidad')
                                    ->suffix(' unid.'),

                                TextEntry::make('precio_unitario')
                                    ->label('Precio Unit.')
                                    ->money('PYG'),

                                TextEntry::make('porcentaje_iva')
                                    ->label('% IVA')
                                    ->suffix('%'),

                                TextEntry::make('monto_total_linea')
                                    ->label('Total Línea')
                                    ->money('PYG')
                                    ->weight('bold')
                                    ->color('success'),
                            ])
                            ->columns(6)
                    ]),

                InfoSection::make('Totales de la Factura')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('total_gravada')
                            ->label('Total Gravada')
                            ->money('PYG')
                            ->state(function ($record) {
                                return $record->detalles->sum('monto_total_linea');
                            })
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('total_iva')
                            ->label('Total IVA (10%)')
                            ->money('PYG')
                            ->state(function ($record) {
                                return $record->detalles->sum(function ($detalle) {
                                    $total = $detalle->monto_total_linea;
                                    return $total * 0.10;
                                });
                            })
                            ->weight('bold')
                            ->size('lg')
                            ->color('warning'),

                        TextEntry::make('total_general')
                            ->label('Total General')
                            ->money('PYG')
                            ->state(function ($record) {
                                $subtotal = $record->detalles->sum('monto_total_linea');
                                $iva = $subtotal * 0.10;
                                return $subtotal + $iva;
                            })
                            ->weight('bold')
                            ->size('xl')
                            ->color('success'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_compra_cabecera')
                    ->label('#')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('proveedor.personas_pro.nombre_completo')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 30) {
                            return $state;
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('ser_comprobante')
                    ->label('Serie-Número')
                    ->formatStateUsing(fn ($record) =>
                        $record->ser_comprobante . '-' . $record->nro_comprobante
                    )
                    ->searchable(['ser_comprobante', 'nro_comprobante'])
                    ->copyable()
                    ->copyMessage('Número copiado')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('fec_comprobante')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('condicionCompra.descripcion')
                    ->label('Condición')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Contado' => 'success',
                        'Crédito' => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('estado_recepcion')
                    ->label('Estado Recepción')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'RECEPCIONADO' => 'success',
                        'PARCIAL' => 'warning',
                        'PENDIENTE' => 'gray',
                        default => 'gray',
                    })
                    ->tooltip(fn ($record) =>
                        $record->porcentaje_recepcion > 0 && $record->porcentaje_recepcion < 100
                            ? "Recepcionado: {$record->porcentaje_recepcion}%"
                            : null
                    ),

              /*  Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pendiente' => 'warning',
                        'Procesado' => 'success',
                        'Anulado' => 'danger',
                        default => 'gray',
                    }),*/


                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('PYG')
                    ->state(function ($record) {
                        return $record->detalles->sum('monto_total_linea');
                    }),
            ])
            ->defaultSort('id_compra_cabecera', 'desc')
            ->filters([


                Tables\Filters\Filter::make('fec_comprobante')
                    ->form([
                        Forms\Components\DatePicker::make('desde')
                            ->label('Desde'),
                        Forms\Components\DatePicker::make('hasta')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['desde'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fec_comprobante', '>=', $date),
                            )
                            ->when(
                                $data['hasta'],
                                fn (Builder $query, $date): Builder => $query->whereDate('fec_comprobante', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Ver')
                        ->color('info'),

                    Tables\Actions\EditAction::make()
                        ->label('Editar')
                        ->color('warning'),

                    Tables\Actions\Action::make('anular')
                        ->label('Anular')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Anular Factura de Compra')
                        ->modalDescription('¿Está seguro que desea anular esta factura? Esta acción no se puede deshacer.')
                        ->modalSubmitActionLabel('Sí, anular')
                        ->action(function (CompraCabecera $record) {
                            $record->update(['estado' => 'Anulado']);
                            Notification::make()
                                ->title('✅ Factura anulada')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (CompraCabecera $record) => $record->estado !== 'Anulado'),
                ])
                ->tooltip('Acciones')
            ]);

    }

    public static function getRelations(): array
    {
        return [
            // CompraDetalleRelationManager::class, // Comentado para usar solo el Repeater
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCompraCabeceras::route('/'),
            'create' => Pages\CreateCompraCabecera::route('/create'),
            'edit' => Pages\EditCompraCabecera::route('/{record}/edit'),
        ];
    }
}
