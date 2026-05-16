<?php

namespace App\Filament\Resources;

use Filament\Tables;
use App\Models\Almacen;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use App\Models\CompraCabecera;
use Filament\Resources\Resource;
use App\Models\GuiaRemisionCabecera;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\RepeatableEntry;
use App\Filament\Resources\GuiaRemisionResource\Pages;

class GuiaRemisionResource extends Resource
{
    protected static ?string $model = GuiaRemisionCabecera::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Gestión Compras';
    protected static ?string $navigationLabel = 'Nota de Remisión';

    protected static ?int $navigationSort = 5;

    // Cargar relaciones necesarias para la vista
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'compraCabecera.proveedor.personas_pro',
                'proveedor.personas_pro',
                'sucursal',
                'detalles.articulo'
            ]);
    }



    public static function form(Form $form): Form
    {
        return $form->schema([
            // Sección superior: Factura y Proveedor lado a lado
            Section::make('Vinculación con Factura y Proveedor')
                ->description('Seleccione una factura pendiente o elija un proveedor manualmente')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('compra_cabecera_id')
                            ->label('Factura de Compra (Opcional)')
                            ->options(function () {
                                return CompraCabecera::with(['proveedor.personas_pro', 'detalles'])
                                    ->get()
                                    ->filter(function ($compra) {
                                        return !$compra->esta_completamente_recepcionada;
                                    })
                                    ->mapWithKeys(function ($compra) {
                                        $proveedor = $compra->proveedor?->personas_pro?->nombre_completo
                                            ?? $compra->proveedor?->nombre
                                            ?? 'Sin proveedor';
                                        $fecha = $compra->fec_comprobante?->format('d/m/Y') ?? 'Sin fecha';
                                        $serie = $compra->ser_comprobante ?? '';
                                        $numero = $compra->nro_comprobante ?? '';
                                        $porcentaje = $compra->porcentaje_recepcion;

                                        $estado = $porcentaje > 0 ? "[{$porcentaje}% recibido]" : '[Pendiente]';
                                        $label = "Factura {$serie}-{$numero} | {$proveedor} | {$fecha} {$estado}";

                                        return [$compra->id_compra_cabecera => $label];
                                    });
                            })
                            ->helperText(fn(Get $get) => 
                                filled($get('cod_proveedor')) 
                                    ? 'Deshabilitado porque seleccionó un proveedor' 
                                    : 'Solo facturas con artículos pendientes'
                            )
                            ->searchable()
                            ->preload()
                            ->live()
                            ->dehydrated()
                            ->disabled(fn(Get $get) => filled($get('cod_proveedor')))
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if (blank($state)) {
                                    $set('cod_proveedor', null);
                                    $set('proveedor_info', null);
                                    $set('tip_factura', null);
                                    $set('ser_factura', null);
                                    $set('nro_factura', null);
                                    $set('detalles', []);
                                    return;
                                }
                                $compra = CompraCabecera::with('proveedor.personas_pro', 'detalles.articulo', 'sucursal')->find($state);
                                if ($compra) {
                                    $proveedorNombre = $compra->proveedor?->personas_pro?->nombre_completo
                                        ?? $compra->proveedor?->nombre
                                        ?? 'Sin proveedor';
                                    $proveedorRuc = $compra->proveedor?->personas_pro?->documento_nro
                                        ?? $compra->proveedor?->personas_pro?->ruc
                                        ?? 'Sin RUC';

                                    $set('cod_proveedor', $compra->proveedor?->cod_proveedor);
                                    $set('proveedor_info', "{$proveedorNombre} - RUC: {$proveedorRuc}");
                                    
                                    // Establecer los campos compuestos de la factura
                                    $set('tip_factura', $compra->tip_comprobante);
                                    $set('ser_factura', $compra->ser_comprobante);
                                    $set('nro_factura', $compra->nro_comprobante);

                                    // Calcular cantidades pendientes por artículo
                                    $items = $compra->detalles
                                        ->filter(fn($detalle) => $detalle->cantidad_pendiente > 0)
                                        ->map(fn($detalle) => [
                                            'articulo_id' => $detalle->cod_articulo,
                                            'articulo_nombre' => $detalle->articulo->descripcion ?? 'Sin descripción',
                                            'cantidad_facturada' => $detalle->cantidad,
                                            'cantidad_ya_recibida' => $detalle->cantidad_recibida,
                                            'cantidad_pendiente' => $detalle->cantidad_pendiente,
                                            'cantidad_recibida' => $detalle->cantidad_pendiente,
                                        ])->values()->toArray();
                                    $set('detalles', $items);
                                }
                            }),

                        Select::make('cod_proveedor')
                            ->label('Proveedor')
                            ->relationship('proveedor', 'cod_proveedor')
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                $record->personas_pro?->nombre_completo ?? 
                                $record->personas_pro?->razon_social ?? 
                                'Sin nombre'
                            )
                            ->searchable(['personas_pro.nombres', 'personas_pro.apellidos', 'personas_pro.razon_social'])
                            ->preload()
                            ->live()
                            ->dehydrated()
                            ->afterStateUpdated(function (Set $set, ?string $state) {
                                if (blank($state)) {
                                    $set('proveedor_info', null);
                                    return;
                                }
                                
                                // Limpiar factura cuando se selecciona proveedor manualmente
                                $set('compra_cabecera_id', null);
                                
                                $proveedor = \App\Models\Proveedor::with('personas_pro')->find($state);
                                if ($proveedor) {
                                    $nombre = $proveedor->personas_pro?->nombre_completo
                                        ?? $proveedor->personas_pro?->razon_social
                                        ?? 'Sin nombre';
                                    $ruc = $proveedor->personas_pro?->documento_nro
                                        ?? $proveedor->personas_pro?->ruc
                                        ?? 'Sin RUC';

                                    $set('proveedor_info', "{$nombre} - RUC: {$ruc}");
                                }
                            })
                            ->disabled(fn(Get $get) => filled($get('compra_cabecera_id')))
                            ->required(fn(Get $get) => blank($get('compra_cabecera_id')))
                            ->helperText(fn(Get $get) => 
                                filled($get('compra_cabecera_id')) 
                                    ? 'Se establece desde la factura' 
                                    : 'Obligatorio si no hay factura'
                            ),
                    ]),

                    TextInput::make('proveedor_info')
                        ->label('Información del Proveedor')
                        ->disabled()
                        ->visible(fn(Get $get) => filled($get('proveedor_info')))
                        ->columnSpanFull(),
                ])
                ->columns(1)
                ->collapsible(),

            // Sección de datos del comprobante
            Section::make('Datos del Comprobante')
                ->schema([
                    Grid::make(4)->schema([
                        TextInput::make('tipo_comprobante')
                            ->label('Tipo')
                            ->default('REM')
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        TextInput::make('ser_remision')
                            ->label('Serie')
                            ->default('001-001')
                            ->maxLength(10)
                            ->required()
                            ->helperText('Formato: 001-001'),

                        TextInput::make('numero_remision')
                            ->label('Número')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxLength(7)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                if (blank($state)) {
                                    return;
                                }

                                // Autocompletar con ceros a la izquierda hasta 7 dígitos
                                $numeroFormateado = str_pad($state, 7, '0', STR_PAD_LEFT);
                                $set('numero_remision', $numeroFormateado);

                                // Buscar timbrado válido automáticamente
                                $codProveedor = $get('cod_proveedor');
                                $tipo = $get('tipo_comprobante');
                                $serie = $get('ser_remision');

                                if (!$codProveedor || !$tipo || !$serie) {
                                    return;
                                }

                                $timbrado = DB::table('timbrado_proveedor')
                                    ->where('cod_proveedor', $codProveedor)
                                    ->where('ser_timbrado', $serie)
                                    ->where('ind_activo', true)
                                    ->where('fec_vencimiento', '>=', now()->format('Y-m-d'))
                                    ->first();

                                if ($timbrado) {
                                    // Verificar que el número esté en el rango
                                    $nroRemision = (int) $state;
                                    if ($nroRemision >= $timbrado->numero_inicial && $nroRemision <= $timbrado->numero_final) {
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
                                        ->body('No existe timbrado válido para este proveedor y serie. Use el botón [+] en el campo Timbrado.')
                                        ->warning()
                                        ->duration(5000)
                                        ->send();
                                }
                            })
                            ->rules([
                                function (Get $get, $record) {
                                    return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                                        $serie = $get('ser_remision');
                                        $proveedor = $get('cod_proveedor');
                                        
                                        if (!$serie || !$proveedor) {
                                            return; // No validar si falta información
                                        }
                                        
                                        $query = GuiaRemisionCabecera::where('numero_remision', $value)
                                            ->where('ser_remision', $serie)
                                            ->where('cod_proveedor', $proveedor);
                                        
                                        // Si estamos editando, excluir el registro actual
                                        if ($record) {
                                            $query->where('id', '!=', $record->id);
                                        }
                                        
                                        $existe = $query->exists();
                                        
                                        if ($existe) {
                                            Notification::make()
                                                ->title('❌ Número de Remisión Duplicado')
                                                ->body("El número **{$value}** ya está registrado para este proveedor y serie. Por favor, ingrese un número diferente.")
                                                ->danger()
                                                ->persistent()
                                                ->send();
                                            
                                            $fail('Este número de remisión ya existe para este proveedor y serie.');
                                        }
                                    };
                                }
                            ])
                            ->helperText('Ej: 4 → 0000004'),

                        TextInput::make('timbrado')
                            ->label('Timbrado')
                            ->numeric()
                            ->maxLength(15)
                            ->required()
                            ->helperText('Se carga automáticamente')
                            ->suffixAction(
                                Action::make('registrarTimbrado')
                                    ->icon('heroicon-o-plus-circle')
                                    ->tooltip('Registrar nuevo timbrado')
                                    ->modalHeading('📋 Registrar Nuevo Timbrado')
                                    ->modalDescription(function (Get $get) {
                                        $proveedor = \App\Models\Proveedor::with('personas_pro')->find($get('cod_proveedor'));
                                        $nombreProveedor = $proveedor ? ($proveedor->personas_pro->nombre_completo ?? $proveedor->personas_pro->razon_social ?? 'N/A') : 'No seleccionado';
                                        return "Proveedor: {$nombreProveedor} | Complete los datos del nuevo timbrado";
                                    })
                                    ->modalWidth('2xl')
                                    ->disabled(fn(Get $get) => blank($get('cod_proveedor')))
                                    ->fillForm(function (Get $get) {
                                        return [
                                            'ser_timbrado_nuevo' => $get('ser_remision'),
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
                                                
                                                Toggle::make('ind_activo_nuevo')
                                                    ->label('Activo')
                                                    ->default(true)
                                                    ->inline(false)
                                                    ->columnSpan(2),
                                            ]),
                                    ])
                                    ->action(function (array $data, Get $get, Set $set) {
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
                                            // Insertar nuevo timbrado
                                            DB::table('timbrado_proveedor')->insert([
                                                'cod_proveedor' => $codProveedor,
                                                'num_timbrado' => $data['num_timbrado_nuevo'],
                                                'ser_timbrado' => $data['ser_timbrado_nuevo'],
                                                'fecha_inicial' => $data['fecha_inicial_nuevo'],
                                                'fec_vencimiento' => $data['fec_vencimiento_nuevo'],
                                                'numero_inicial' => $data['numero_inicial_nuevo'],
                                                'numero_final' => $data['numero_final_nuevo'],
                                                'ind_activo' => $data['ind_activo_nuevo'] ?? true,
                                            ]);

                                            // Establecer el timbrado en el formulario
                                            $set('timbrado', $data['num_timbrado_nuevo']);

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
                    ]),

                    Grid::make(3)->schema([
                        DatePicker::make('fecha_remision')
                            ->label('Fecha de Remisión')
                            ->default(now())
                            ->required(),

                        TextInput::make('sucursal_destino')
                            ->label('Sucursal Destino')
                            ->disabled()
                            ->helperText('Stock ingresará a esta sucursal'),

                        TextInput::make('usuario_carga')
                            ->label('Usuario')
                            ->disabled(),

                        Placeholder::make('fec_alta_display')
                            ->label('Fecha de Alta')
                            ->content(fn () => now()->format('d/m/Y H:i:s')),
                    ]),

                    Hidden::make('cod_sucursal')
                        ->dehydrated(),

                    Hidden::make('almacen_id')
                        ->dehydrated(),

                    // Campos ocultos críticos: proveedor y factura
                    Hidden::make('cod_proveedor')
                        ->dehydrated(),

                    Hidden::make('compra_cabecera_id')
                        ->dehydrated(),

                    // Campos ocultos para la relación compuesta con la factura
                    Hidden::make('tip_factura')
                        ->dehydrated(),

                    Hidden::make('ser_factura')
                        ->dehydrated(),

                    Hidden::make('nro_factura')
                        ->dehydrated(),
                ])
                ->columns(1),

            Section::make('Ítems a Recibir')->schema([
                Repeater::make('detalles')
                    ->label('')
                    ->schema([
                        // Modo Manual: Selector de artículo
                        Select::make('articulo_id')
                            ->label('Artículo')
                            ->options(function () {
                                return \App\Models\Articulos::where('activo', 1)
                                    ->orderBy('descripcion')
                                    ->get()
                                    ->mapWithKeys(function ($articulo) {
                                        return [$articulo->cod_articulo => $articulo->descripcion . ' (Cód: ' . $articulo->cod_articulo . ')'];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(3)
                            ->disabled(fn(Get $get) => filled($get('../../compra_cabecera_id')))
                            ->visible(fn(Get $get) => blank($get('../../compra_cabecera_id'))),

                        // Modo Factura: Nombre del artículo (solo lectura)
                        TextInput::make('articulo_nombre')
                            ->label('Artículo')
                            ->disabled()
                            ->columnSpan(3)
                            ->visible(fn(Get $get) => filled($get('../../compra_cabecera_id'))),

                        // Campos de cantidad con factura
                        TextInput::make('cantidad_facturada')
                            ->label('Facturada')
                            ->disabled()
                            ->numeric()
                            ->suffix('u.')
                            ->columnSpan(1)
                            ->visible(fn(Get $get) => filled($get('../../compra_cabecera_id'))),

                        TextInput::make('cantidad_ya_recibida')
                            ->label('Ya Recibida')
                            ->disabled()
                            ->numeric()
                            ->suffix('u.')
                            ->default(0)
                            ->columnSpan(1)
                            ->visible(fn(Get $get) => filled($get('../../compra_cabecera_id'))),

                        TextInput::make('cantidad_pendiente')
                            ->label('Pendiente')
                            ->disabled()
                            ->numeric()
                            ->suffix('u.')
                            ->columnSpan(1)
                            ->visible(fn(Get $get) => filled($get('../../compra_cabecera_id'))),

                        // Campo de cantidad a recibir
                        TextInput::make('cantidad_recibida')
                            ->label(fn(Get $get) => 
                                filled($get('../../compra_cabecera_id')) 
                                    ? 'A Recibir' 
                                    : 'Cantidad'
                            )
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(fn(Get $get) => 
                                filled($get('../../compra_cabecera_id')) 
                                    ? $get('cantidad_pendiente') 
                                    : null
                            )
                            ->suffix('u.')
                            ->columnSpan(fn(Get $get) => 
                                filled($get('../../compra_cabecera_id')) ? 1 : 3
                            )
                            ->helperText(fn(Get $get) => 
                                filled($get('../../compra_cabecera_id')) && $get('cantidad_pendiente')
                                    ? 'Máx: ' . $get('cantidad_pendiente')
                                    : null
                            ),

                        Hidden::make('articulo_id')
                            ->visible(fn(Get $get) => filled($get('../../compra_cabecera_id'))),
                    ])
                    ->columns(6)
                    ->reorderable(false)
                    ->addable(fn(Get $get) => blank($get('compra_cabecera_id')))
                    ->deletable(fn(Get $get) => blank($get('compra_cabecera_id')))
                    ->defaultItems(0)
                    ->minItems(1)
                    ->addActionLabel('+ Agregar artículo')
                    ->visible(fn(Get $get) => filled($get('compra_cabecera_id')) ? !empty($get('detalles')) : true),
            ])->columnSpanFull(),
        ])->columns(3);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfoSection::make('Información de la Nota de Remisión')
                    ->schema([
                        TextEntry::make('numero_remision')
                            ->label('Número de Remisión')
                            ->badge()
                            ->color('primary'),
                        TextEntry::make('timbrado')
                            ->label('Timbrado')
                            ->badge()
                            ->color('success'),
                        TextEntry::make('fecha_remision')
                            ->label('Fecha de Remisión')
                            ->date('d/m/Y'),
                        TextEntry::make('tipo_comprobante')
                            ->label('Tipo de Comprobante'),
                        TextEntry::make('ser_remision')
                            ->label('Serie'),
                        TextEntry::make('estado')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'P' => 'warning',
                                'A' => 'success',
                                'N' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (string $state): string => match ($state) {
                                'P' => 'Pendiente',
                                'A' => 'Aprobado',
                                'N' => 'Anulado',
                                default => $state,
                            }),
                    ])
                    ->columns(3),

                InfoSection::make('Datos de la Factura de Compra')
                    ->schema([
                        TextEntry::make('compraCabecera.nro_comprobante')
                            ->label('N° de Factura')
                            ->formatStateUsing(fn ($state, $record) =>
                                ($record->compraCabecera->ser_comprobante ?? '') . '-' . $state
                            )
                            ->default('Sin factura asociada'),
                        TextEntry::make('compraCabecera.fec_comprobante')
                            ->label('Fecha de Factura')
                            ->date('d/m/Y')
                            ->default('N/A'),
                        TextEntry::make('compraCabecera.estado_recepcion')
                            ->label('Estado de Recepción')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'RECEPCIONADO' => 'success',
                                'PARCIAL' => 'warning',
                                'PENDIENTE' => 'gray',
                                default => 'gray',
                            })
                            ->default('N/A'),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record->compra_cabecera_id !== null),

                InfoSection::make('Datos del Proveedor')
                    ->schema([
                        TextEntry::make('proveedor.personas_pro.nombre_completo')
                            ->label('Nombre o Razón Social')
                            ->default(fn ($record) => 
                                // Prioridad: proveedor directo > proveedor de factura
                                $record->proveedor?->personas_pro?->nombre_completo ??
                                $record->proveedor?->personas_pro?->razon_social ??
                                $record->compraCabecera?->proveedor?->personas_pro?->nombre_completo ??
                                $record->compraCabecera?->proveedor?->nombre ??
                                'Sin proveedor'
                            ),
                        TextEntry::make('proveedor.personas_pro.documento_nro')
                            ->label('RUC/Documento')
                            ->default(fn ($record) =>
                                // Prioridad: proveedor directo > proveedor de factura
                                $record->proveedor?->personas_pro?->ruc ??
                                $record->proveedor?->personas_pro?->documento_nro ??
                                $record->compraCabecera?->proveedor?->personas_pro?->ruc ??
                                $record->compraCabecera?->proveedor?->personas_pro?->documento_nro ??
                                'Sin documento'
                            ),
                    ])
                    ->columns(2),

                InfoSection::make('Depósito y Usuario')
                    ->schema([
                        TextEntry::make('sucursal.descripcion')
                            ->label('Depósito Destino (Sucursal)'),
                        TextEntry::make('usuario_alta')
                            ->label('Usuario de Carga')
                            ->default('Sin usuario'),
                        TextEntry::make('fec_alta')
                            ->label('Fecha de Carga')
                            ->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(3),

                InfoSection::make('Detalles de Artículos Recibidos')
                    ->schema([
                        RepeatableEntry::make('detalles')
                            ->label('')
                            ->schema([
                                TextEntry::make('articulo.descripcion')
                                    ->label('Artículo'),
                                TextEntry::make('articulo.codigo')
                                    ->label('Código'),
                                TextEntry::make('cantidad_recibida')
                                    ->label('Cantidad Recibida')
                                    ->badge()
                                    ->color('success'),
                            ])
                            ->columns(3)
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_remision')
                    ->label('N° Remisión')
                    ->searchable(),

                Tables\Columns\TextColumn::make('factura_completa')
                    ->label('N° Factura')
                    ->getStateUsing(function ($record) {
                        // Primero intentar con los campos compuestos (nuevo método)
                        if ($record->ser_factura && $record->nro_factura) {
                            return "{$record->ser_factura}-{$record->nro_factura}";
                        }
                        
                        // Fallback: método antiguo con relación
                        if ($record->compraCabecera) {
                            return "{$record->compraCabecera->ser_comprobante}-{$record->compraCabecera->nro_comprobante}";
                        }
                        
                        return 'Sin factura';
                    })
                    ->searchable()
                    ->sortable(false),

                Tables\Columns\TextColumn::make('proveedor_nombre')
                    ->label('Proveedor')
                    ->getStateUsing(function ($record) {
                        // Primero buscar en la relación directa
                        if ($record->proveedor) {
                            return $record->proveedor->personas_pro?->nombre_completo ??
                                   $record->proveedor->personas_pro?->razon_social ??
                                   'Proveedor #' . $record->cod_proveedor;
                        }
                        
                        // Fallback: buscar en la factura asociada
                        if ($record->compraCabecera?->proveedor) {
                            return $record->compraCabecera->proveedor->personas_pro?->nombre_completo ??
                                   $record->compraCabecera->proveedor->personas_pro?->razon_social ??
                                   'Sin nombre';
                        }
                        
                        return 'Sin proveedor';
                    })
                    ->searchable()
                    ->sortable(false)
                    ->limit(40),

                Tables\Columns\TextColumn::make('sucursal.descripcion')
                    ->label('Sucursal Destino'),

                Tables\Columns\TextColumn::make('fecha_remision')
                    ->label('Fecha')
                    ->date('d/m/Y'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'P' => 'warning',
                        'A' => 'success',
                        'N' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'P' => 'Pendiente',
                        'A' => 'Aprobado',
                        'N' => 'Anulado',
                        default => $state,
                    }),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Ver')
                        ->color('info')
                        ->modalHeading(fn ($record) => 'Nota de Remisión: ' . $record->numero_remision)
                        ->modalWidth('7xl'),

                    Tables\Actions\Action::make('anular')
                        ->label('Anular')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Anular Nota de Remisión')
                        ->modalDescription('Esta acción revertirá el stock ingresado. ¿Está seguro?')
                        ->modalSubmitActionLabel('Sí, anular')
                        ->action(function (GuiaRemisionCabecera $record) {
                            DB::transaction(function () use ($record) {
                                // Reversar el stock
                                foreach ($record->detalles as $detalle) {
                                    $existencia = \App\Models\ExistenciaArticulo::where('cod_articulo', $detalle->articulo_id)
                                        ->where('cod_sucursal', $record->cod_sucursal)
                                        ->first();

                                    if ($existencia) {
                                        // Restar la cantidad que se había agregado
                                        $existencia->decrement('stock_actual', $detalle->cantidad_recibida);
                                        $existencia->update([
                                            'usuario_mod' => auth()->user()->name ?? 'Sistema',
                                            'fec_mod' => now(),
                                        ]);
                                    }
                                }

                                // Cambiar estado a Anulado
                                $record->update([
                                    'estado' => 'N',
                                    'usuario_mod' => auth()->user()->name ?? 'Sistema',
                                    'fec_mod' => now(),
                                ]);
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Nota de Remisión anulada')
                                ->body('El stock ha sido revertido correctamente.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (GuiaRemisionCabecera $record) => $record->estado !== 'N'),
                ])
                ->tooltip('Acciones')
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuiasRemision::route('/'),
            'create' => Pages\CreateGuiaRemision::route('/create'),
            'edit' => Pages\EditGuiaRemision::route('/{record}/edit'),
        ];
    }
}
