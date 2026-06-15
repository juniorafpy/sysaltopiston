<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotaCreditoDebitoCompraResource\Pages;
use App\Filament\Resources\NotaCreditoDebitoCompraResource\RelationManagers;
use App\Models\NotaCreditoDebitoCompra;
use App\Models\CompraCabecera;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class NotaCreditoDebitoCompraResource extends Resource
{
    protected static ?string $model = NotaCreditoDebitoCompra::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
      protected static ?string $navigationGroup = 'Gestión de Compra';
    protected static ?string $navigationLabel = 'Nota de Crédito/Débito';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ─── SECCIÓN 1: PROVEEDOR & FACTURA ───
            Forms\Components\Section::make('')
                ->icon('heroicon-o-building-storefront')
                ->schema([
                    Forms\Components\Grid::make(5)->schema([
                        Forms\Components\Select::make('cod_proveedor')
                            ->label('Proveedor')
                            ->prefixIcon('heroicon-o-user')
                            ->relationship('proveedor', 'cod_proveedor')
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                return $record->personas_pro ? ($record->personas_pro->razon_social ?: trim($record->personas_pro->nombres . ' ' . $record->personas_pro->apellidos)) : $record->cod_proveedor;
                            })
                            ->searchable(['personas_pro.nro_documento', 'personas_pro.nombres', 'personas_pro.apellidos', 'personas_pro.razon_social'])
                            ->preload()
                            ->required()
                            ->columnSpan(3)
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('id_compra_cabecera', null);
                                $set('ser_comprobante', null);
                                $set('timbrado', null);
                                $set('nro_comprobante', null);
                                $set('fec_comprobante', now());
                                $set('detalles', []);
                                $set('total_general', 0);
                            }),

                        Forms\Components\Select::make('id_compra_cabecera')
                            ->label('Factura de Compra')
                            ->prefixIcon('heroicon-o-document-text')
                            ->options(function (callable $get) {
                                $codProveedor = $get('cod_proveedor');
                                if (!$codProveedor) {
                                    return [];
                                }
                                return CompraCabecera::where('cod_proveedor', $codProveedor)
                                    ->with('proveedor')
                                    ->get()
                                    ->mapWithKeys(function ($compra) {
                                        $label = sprintf(
                                            '%s-%s-%s | %s | %s',
                                            $compra->tip_comprobante,
                                            $compra->ser_comprobante,
                                            $compra->nro_comprobante,
                                            $compra->proveedor->nombre ?? 'Sin proveedor',
                                            $compra->fec_comprobante?->format('d/m/Y')
                                        );
                                        return [$compra->id_compra_cabecera => $label];
                                    })
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->columnSpan(2)
                            ->disabled(fn (callable $get) => !$get('cod_proveedor'))
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if (empty($state)) {
                                    $set('detalles', []);
                                    $set('total_general', 0);
                                    return;
                                }
                                $compra = CompraCabecera::find($state);
                                if (!$compra) {
                                    $set('detalles', []);
                                    $set('total_general', 0);
                                    return;
                                }
                                // Solo cargar detalles de la factura; NO precargar serie/timbrado/nro de la factura
                                // La nota tiene su propio timbrado NC/ND
                                if (!$get('cod_proveedor')) {
                                    $set('cod_proveedor', $compra->cod_proveedor);
                                }
                                $detalles = $compra->detalles->map(function ($detalle) {
                                    return [
                                        'cod_articulo' => $detalle->cod_articulo,
                                        'cantidad' => $detalle->cantidad,
                                        'precio_unitario' => $detalle->precio_unitario,
                                        'porcentaje_iva' => $detalle->porcentaje_iva,
                                        'monto_total_linea' => $detalle->monto_total_linea,
                                    ];
                                })->toArray();
                                $set('detalles', $detalles);
                                $set('total_general', collect($detalles)->sum('monto_total_linea'));
                            }),
                    ]),
                ]),

            // ─── SECCIÓN 2: TIPO & DATOS DEL COMPROBANTE ───
            Forms\Components\Section::make('')
                ->icon('heroicon-o-document-check')
                ->schema([
                    Forms\Components\Grid::make(5)->schema([
                        // Badge tipo nota
                        Forms\Components\Select::make('tip_comprobante')
                            ->label('Tipo')
                            ->options([
                                'NC' => '🟢 Nota de Crédito',
                                'ND' => '🔴 Nota de Débito',
                            ])
                            ->required()
                            ->native(false)
                            ->live()
                            ->columnSpan(1)
                            ->afterStateUpdated(function ($state, callable $set) {
                                $set('cod_motivo', null);
                            }),

                        Forms\Components\Select::make('cod_motivo')
                            ->label('Motivo')
                            ->prefixIcon('heroicon-o-flag')
                            ->options(function (callable $get) {
                                $tipoNota = $get('tip_comprobante');
                                if (!$tipoNota) {
                                    return [];
                                }
                                return \App\Models\MotivoNotaCreditoDebito::activos()
                                    ->where('tipo_nota', $tipoNota)
                                    ->pluck('descripcion', 'cod_motivo')
                                    ->toArray();
                            })
                            ->searchable()
                            ->required()
                            ->helperText(function (callable $get) {
                                $motivoId = $get('cod_motivo');
                                if (!$motivoId) {
                                    return null;
                                }
                                $motivo = \App\Models\MotivoNotaCreditoDebito::find($motivoId);
                                if (!$motivo) {
                                    return null;
                                }
                                $textos = [];
                                if ($motivo->afecta_stock) {
                                    $textos[] = '📦 Afecta inventario';
                                }
                                if ($motivo->afecta_saldo) {
                                    $textos[] = '💰 Afecta saldo';
                                }
                                return implode(' | ', $textos);
                            })
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('timbrado')
                            ->label('Timbrado')
                            ->prefixIcon('heroicon-o-shield-check')
                            ->required()
                            ->maxLength(20)
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('fec_comprobante')
                            ->label('Fecha')
                            ->prefixIcon('heroicon-o-calendar')
                            ->default(now())
                            ->required()
                            ->native(true)
                            ->displayFormat('d/m/Y')
                            ->columnSpan(1),
                    ]),

                    Forms\Components\Grid::make(5)->schema([
                        Forms\Components\TextInput::make('ser_comprobante')
                            ->label('Serie')
                            ->prefixIcon('heroicon-o-hashtag')
                            ->required()
                            ->maxLength(10)
                            ->columnSpan(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if (blank($state) || blank($get('cod_proveedor')) || blank($get('tip_comprobante'))) {
                                    return;
                                }
                                // Buscar timbrado válido automáticamente
                                $timbrado = DB::table('timbrado_proveedor')
                                    ->where('cod_proveedor', $get('cod_proveedor'))
                                    ->where('ser_timbrado', $state)
                                    ->where('tip_comprobante', $get('tip_comprobante'))
                                    ->where('ind_activo', true)
                                    ->where('fec_vencimiento', '>=', now()->format('Y-m-d'))
                                    ->first();
                                if ($timbrado) {
                                    $set('timbrado', $timbrado->num_timbrado);
                                    Notification::make()
                                        ->title('✅ Timbrado cargado')
                                        ->body("Timbrado: {$timbrado->num_timbrado} (Rango: {$timbrado->numero_inicial}-{$timbrado->numero_final})")
                                        ->success()
                                        ->duration(3000)
                                        ->send();
                                } else {
                                    $set('timbrado', null);
                                    Notification::make()
                                        ->title('⚠️ Timbrado no encontrado')
                                        ->body("No existe timbrado válido para NC/ND con esta serie y proveedor.")
                                        ->warning()
                                        ->duration(5000)
                                        ->send();
                                }
                            }),

                        Forms\Components\TextInput::make('nro_comprobante')
                            ->label('Número')
                            ->prefixIcon('heroicon-o-document-text')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxLength(20)
                            ->columnSpan(1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if (blank($state) || blank($get('cod_proveedor')) || blank($get('ser_comprobante')) || blank($get('tip_comprobante'))) {
                                    return;
                                }
                                $timbrado = DB::table('timbrado_proveedor')
                                    ->where('cod_proveedor', $get('cod_proveedor'))
                                    ->where('ser_timbrado', $get('ser_comprobante'))
                                    ->where('tip_comprobante', $get('tip_comprobante'))
                                    ->where('ind_activo', true)
                                    ->where('fec_vencimiento', '>=', now()->format('Y-m-d'))
                                    ->first();
                                if ($timbrado) {
                                    $nro = (int) $state;
                                    if ($nro >= $timbrado->numero_inicial && $nro <= $timbrado->numero_final) {
                                        $set('timbrado', $timbrado->num_timbrado);
                                        // Nothing else needed
                                    } else {
                                        $set('timbrado', null);
                                        Notification::make()
                                            ->title('⚠️ Número fuera de rango')
                                            ->body("El número debe estar entre {$timbrado->numero_inicial} y {$timbrado->numero_final}")
                                            ->warning()
                                            ->duration(5000)
                                            ->send();
                                    }
                                } else {
                                    $set('timbrado', null);
                                    Notification::make()
                                        ->title('⚠️ Timbrado no encontrado')
                                        ->body("No existe timbrado válido para NC/ND con esta serie y proveedor.")
                                        ->warning()
                                        ->duration(5000)
                                        ->send();
                                }
                            })
                            ->rules([
                                function (callable $get) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get) {
                                        $codProveedor = $get('cod_proveedor');
                                        $serie = $get('ser_comprobante');
                                        $tipo = $get('tip_comprobante');
                                        if (!$codProveedor || !$serie || !$tipo) {
                                            return;
                                        }
                                        $timbrado = DB::table('timbrado_proveedor')
                                            ->where('cod_proveedor', $codProveedor)
                                            ->where('ser_timbrado', $serie)
                                            ->where('tip_comprobante', $tipo)
                                            ->where('ind_activo', true)
                                            ->where('fec_vencimiento', '>=', now()->format('Y-m-d'))
                                            ->first();
                                        if (!$timbrado) {
                                            $fail('⚠️ No existe timbrado válido para este tipo de nota y serie.');
                                            return;
                                        }
                                        $nro = (int) $value;
                                        if ($nro < $timbrado->numero_inicial || $nro > $timbrado->numero_final) {
                                            $fail("⚠️ El número debe estar entre {$timbrado->numero_inicial} y {$timbrado->numero_final}");
                                        }
                                    };
                                }
                            ]),

                        Forms\Components\TextInput::make('timbrado')
                            ->label('Timbrado')
                            ->prefixIcon('heroicon-o-shield-check')
                            ->required()
                            ->numeric()
                            ->maxLength(15)
                            ->columnSpan(1)
                            ->helperText('Se carga automáticamente')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('registrarTimbrado')
                                    ->icon('heroicon-o-plus-circle')
                                    ->tooltip('Registrar nuevo timbrado')
                                    ->modalHeading('📋 Registrar Nuevo Timbrado')
                                    ->modalDescription(function (callable $get) {
                                        $proveedor = \App\Models\Proveedor::with('personas_pro')->find($get('cod_proveedor'));
                                        $nombre = $proveedor ? ($proveedor->personas_pro->nombre_completo ?? $proveedor->personas_pro->razon_social ?? 'N/A') : 'No seleccionado';
                                        return "Proveedor: {$nombre} | Complete los datos del nuevo timbrado";
                                    })
                                    ->modalWidth('2xl')
                                    ->disabled(function (callable $get) {
                                        return blank($get('cod_proveedor'));
                                    })
                                    ->fillForm(function (callable $get) {
                                        return [
                                            'ser_timbrado_nuevo' => $get('ser_comprobante'),
                                            'tip_comprobante_nuevo' => $get('tip_comprobante'),
                                        ];
                                    })
                                    ->form([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('num_timbrado_nuevo')
                                                    ->label('Número de Timbrado')
                                                    ->required()
                                                    ->numeric()
                                                    ->maxLength(15)
                                                    ->placeholder('12345678')
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('ser_timbrado_nuevo')
                                                    ->label('Serie del Timbrado')
                                                    ->required()
                                                    ->maxLength(10)
                                                    ->placeholder('001-001')
                                                    ->helperText('Formato: 001-001')
                                                    ->columnSpan(1),

                                                Forms\Components\Select::make('tip_comprobante_nuevo')
                                                    ->label('Tipo Comprobante')
                                                    ->required()
                                                    ->options(['NC' => 'Nota de Crédito', 'ND' => 'Nota de Débito'])
                                                    ->native(false)
                                                    ->columnSpan(1),

                                                Forms\Components\DatePicker::make('fecha_inicial_nuevo')
                                                    ->label('Fecha Inicial')
                                                    ->required()
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y')
                                                    ->default(now())
                                                    ->columnSpan(1),

                                                Forms\Components\DatePicker::make('fec_vencimiento_nuevo')
                                                    ->label('Fecha Vencimiento')
                                                    ->required()
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y')
                                                    ->after('fecha_inicial_nuevo')
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('numero_inicial_nuevo')
                                                    ->label('Número Inicial')
                                                    ->required()
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('1')
                                                    ->columnSpan(1),

                                                Forms\Components\TextInput::make('numero_final_nuevo')
                                                    ->label('Número Final')
                                                    ->required()
                                                    ->numeric()
                                                    ->minValue(1)
                                                    ->placeholder('9999999')
                                                    ->columnSpan(1),

                                                Forms\Components\Toggle::make('ind_activo_nuevo')
                                                    ->label('Activo')
                                                    ->default(true)
                                                    ->inline(false)
                                                    ->columnSpan(2),
                                            ]),
                                    ])
                                    ->action(function (array $data, callable $get, callable $set) {
                                        $codProveedor = $get('cod_proveedor');
                                        if (!$codProveedor) {
                                            Notification::make()
                                                ->title('❌ Error')
                                                ->body('Debe seleccionar un proveedor primero.')
                                                ->danger()
                                                ->send();
                                            return;
                                        }
                                        try {
                                            DB::table('timbrado_proveedor')->insert([
                                                'cod_proveedor' => $codProveedor,
                                                'num_timbrado' => $data['num_timbrado_nuevo'],
                                                'ser_timbrado' => $data['ser_timbrado_nuevo'],
                                                'tip_comprobante' => $data['tip_comprobante_nuevo'],
                                                'fecha_inicial' => $data['fecha_inicial_nuevo'],
                                                'fec_vencimiento' => $data['fec_vencimiento_nuevo'],
                                                'numero_inicial' => $data['numero_inicial_nuevo'],
                                                'numero_final' => $data['numero_final_nuevo'],
                                                'ind_activo' => $data['ind_activo_nuevo'] ?? true,
                                            ]);
                                            $set('timbrado', $data['num_timbrado_nuevo']);
                                            $set('ser_comprobante', $data['ser_timbrado_nuevo']);
                                            Notification::make()
                                                ->title('✅ Timbrado registrado')
                                                ->body("Timbrado {$data['num_timbrado_nuevo']} creado exitosamente.")
                                                ->success()
                                                ->send();
                                        } catch (\Exception $e) {
                                            Notification::make()
                                                ->title('❌ Error al registrar')
                                                ->body('No se pudo registrar el timbrado: ' . $e->getMessage())
                                                ->danger()
                                                ->send();
                                        }
                                    })
                            ),

                        Forms\Components\Textarea::make('observacion')
                            ->label('Descripción / Concepto')
                            ->placeholder('Ingrese el motivo o concepto de esta nota...')
                            ->rows(2)
                            ->columnSpan(2),
                    ]),
                ]),

            // ─── SECCIÓN 3: DETALLE DE ARTÍCULOS ───
            Forms\Components\Section::make('')
                ->icon('heroicon-o-shopping-cart')
                ->schema([
                    Forms\Components\Repeater::make('detalles')
                        ->relationship('detalles')
                        ->label('')
                        ->addActionLabel('➕ Agregar artículo')
                        ->schema([
                            Forms\Components\Grid::make(12)->schema([
                                Forms\Components\Select::make('cod_articulo')
                                    ->label('Artículo')
                                    ->prefixIcon('heroicon-o-cube')
                                    ->relationship('articulo', 'descripcion')
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(5),

                                Forms\Components\TextInput::make('cantidad')
                                    ->label('Cant.')
                                    ->prefixIcon('heroicon-o-calculator')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $precio = (float) ($get('precio_unitario') ?? 0);
                                        $iva = (float) ($get('porcentaje_iva') ?? 10);
                                        $subtotal = $state * $precio;
                                        $montoIva = $subtotal * ($iva / 100);
                                        $set('monto_total_linea', round($subtotal + $montoIva, 0));
                                        
                                        $detalles = $get('../../detalles') ?? [];
                                        $totalGeneral = collect($detalles)->sum(fn ($item) => (float) ($item['monto_total_linea'] ?? 0));
                                        $set('../../total_general', round($totalGeneral, 0));
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('precio_unitario')
                                    ->label('Precio')
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->numeric()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $cantidad = (float) ($get('cantidad') ?? 0);
                                        $iva = (float) ($get('porcentaje_iva') ?? 10);
                                        $subtotal = $cantidad * $state;
                                        $montoIva = $subtotal * ($iva / 100);
                                        $set('monto_total_linea', round($subtotal + $montoIva, 0));
                                        
                                        $detalles = $get('../../detalles') ?? [];
                                        $totalGeneral = collect($detalles)->sum(fn ($item) => (float) ($item['monto_total_linea'] ?? 0));
                                        $set('../../total_general', round($totalGeneral, 0));
                                    })
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('porcentaje_iva')
                                    ->label('IVA')
                                    ->suffix('%')
                                    ->numeric()
                                    ->default(10)
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $cantidad = (float) ($get('cantidad') ?? 0);
                                        $precio = (float) ($get('precio_unitario') ?? 0);
                                        $subtotal = $cantidad * $precio;
                                        $montoIva = $subtotal * ($state / 100);
                                        $set('monto_total_linea', round($subtotal + $montoIva, 0));
                                        
                                        $detalles = $get('../../detalles') ?? [];
                                        $totalGeneral = collect($detalles)->sum(fn ($item) => (float) ($item['monto_total_linea'] ?? 0));
                                        $set('../../total_general', round($totalGeneral, 0));
                                    })
                                    ->columnSpan(1),

                                Forms\Components\TextInput::make('monto_total_linea')
                                    ->label('Total')
                                    ->prefix('Gs.')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated()
                                    ->extraInputAttributes(['class' => 'font-bold text-success-600'])
                                    ->columnSpan(2),
                            ]),
                        ])
                        ->default([])
                        ->live()
                        ->afterStateUpdated(function (callable $set, $state) {
                            $totalGeneral = collect($state)->sum(fn ($item) => (float) ($item['monto_total_linea'] ?? 0));
                            $set('total_general', round($totalGeneral, 0));
                        })
                        ->deletable()
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(fn (array $state): ?string => $state['articulo_id'] ?? null),
                ]),

            // ─── SECCIÓN 4: TOTAL ───
            Forms\Components\Section::make('')
                ->icon('heroicon-o-calculator')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Placeholder::make('info_total')
                            ->label('')
                            ->content(function (callable $get) {
                                $tipo = $get('tip_comprobante');
                                $signo = $tipo === 'ND' ? '+' : '-';
                                return new \Illuminate\Support\HtmlString(
                                    "<span class=\"text-sm text-gray-500\">Este comprobante {$signo} afecta el saldo de la factura</span>"
                                );
                            })
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('total_general')
                            ->label('TOTAL')
                            ->prefix('Gs.')
                            ->disabled()
                            ->dehydrated()
                            ->extraInputAttributes([
                                'class' => 'text-2xl font-extrabold text-primary-600',
                                'style' => 'text-align: right;',
                            ])
                            ->columnSpan(1),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_completo')
                    ->label('Nro. Comprobante')
                    ->searchable(['nro_comprobante', 'ser_comprobante'])
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('tip_comprobante')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'NC',
                        'warning' => 'ND',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'NC' ? 'Nota Crédito' : 'Nota Débito'),
                Tables\Columns\TextColumn::make('fec_comprobante')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('compraCabecera.numero_completo')
                    ->label('Factura Origen')
                    ->searchable(),
                Tables\Columns\TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('motivo.descripcion')
                    ->label('Motivo')
                    ->searchable()
                    ->badge()
                    ->color(fn ($record) => $record->motivo?->afecta_stock ? 'warning' : 'info'),
                Tables\Columns\TextColumn::make('total_nota')
                    ->label('Total')
                    ->money('PYG', divideBy: 1)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tip_comprobante')
                    ->label('Tipo')
                    ->options([
                        'NC' => 'Nota de Crédito',
                        'ND' => 'Nota de Débito',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('fec_comprobante', 'desc');
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
            'index' => Pages\ListNotaCreditoDebitoCompras::route('/'),
            'create' => Pages\CreateNotaCreditoDebitoCompra::route('/create'),
            'edit' => Pages\EditNotaCreditoDebitoCompra::route('/{record}/edit'),
        ];
    }
}
