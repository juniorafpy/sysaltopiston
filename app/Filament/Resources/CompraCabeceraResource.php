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
use Filament\Forms\Components\Textarea;
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
    protected static ?string $navigationGroup = 'Gestión de Compra';
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
                // Campo oculto para tipo de comprobante (siempre es FAC - Factura)
                Forms\Components\Hidden::make('tip_comprobante')
                    ->default('FAC'),
                
                // Layout con Grid: Información Principal (2 cols) + Vencimiento (1 col)
                Grid::make(3)
                    ->schema([
                        Section::make('Información Principal')
                            ->description('Datos del proveedor y condiciones de compra')
                            ->columnSpan(2)
                            ->schema([
                                // Grid con Sucursal, Fecha y Condición en la misma fila
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        // Sucursal
                                        Select::make('cod_sucursal')
                                            ->label('Sucursal')
                                            ->options(\App\Models\Sucursal::pluck('descripcion', 'cod_sucursal'))
                                            ->default(fn () => auth()->user()->cod_sucursal)
                                            ->required()
                                            ->disabled(fn ($context) => $context === 'edit' || (request()->query('orden_compra') && $context === 'create'))
                                            ->dehydrated()
                                            ->searchable()
                                            ->preload()
                                            ->columnSpan(1),

                                        // Fecha de Factura
                                        DatePicker::make('fec_comprobante')
                                            ->label('Fecha de Factura')
                                            ->required()
                                            ->default(now())
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                $condicionId = $get('cod_condicion_compra');
                                                if ($condicionId && $state) {
                                                    $condicion = \App\Models\CondicionCompra::find($condicionId);
                                                    if ($condicion) {
                                                        // Extraer días de la descripción (ej: "30 DIAS" -> 30)
                                                        preg_match('/(\d+)/', $condicion->descripcion, $matches);
                                                        $dias = $matches[1] ?? 30;
                                                        $fechaVencimiento = \Carbon\Carbon::parse($state)->addDays($dias);
                                                        $set('fec_vencimiento', $fechaVencimiento->format('Y-m-d'));
                                                    }
                                                }
                                            })
                                            ->columnSpan(1),

                                        // Condición de Compra
                                        Select::make('cod_condicion_compra')
                                            ->label('Condición de Compra')
                                            ->options(function () {
                                                return \App\Models\CondicionCompra::all()
                                                    ->mapWithKeys(function ($condicion) {
                                                        $cuotasInfo = $condicion->cant_cuota > 0
                                                            ? " ({$condicion->cant_cuota} cuotas)"
                                                            : ' (Contado)';
                                                        return [$condicion->cod_condicion => $condicion->descripcion . $cuotasInfo];
                                                    });
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->disabled(fn ($context) => request()->query('orden_compra') && $context === 'create')
                                            ->dehydrated()
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                if ($state) {
                                                    $condicion = \App\Models\CondicionCompra::find($state);
                                                    $fechaComprobante = $get('fec_comprobante');
                                                    
                                                    if ($condicion && $fechaComprobante) {
                                                        // Extraer días de la descripción (ej: "30 DIAS" -> 30)
                                                        preg_match('/(\d+)/', $condicion->descripcion, $matches);
                                                        $dias = $matches[1] ?? 30;
                                                        $fechaVencimiento = \Carbon\Carbon::parse($fechaComprobante)->addDays($dias);
                                                        $set('fec_vencimiento', $fechaVencimiento->format('Y-m-d'));
                                                    }
                                                }
                                            })
                                            ->columnSpan(1),
                                    ]),

                                // Grid para Proveedor, Serie y Nro Factura
                                Forms\Components\Grid::make(5)
                                    ->schema([
                                        // Proveedor
                                        Select::make('cod_proveedor')
                                            ->label('Proveedor')
                                            ->options(function () {
                                                return \App\Models\Proveedor::with('personas_pro')
                                                    ->get()
                                                    ->mapWithKeys(function ($proveedor) {
                                                        $label = $proveedor->personas_pro?->nombre_completo ?? $proveedor->nombre ?? 'N/A';
                                                        return [$proveedor->cod_proveedor => $label];
                                                    });
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            //->helperText('Seleccione el proveedor de la factura')
                                            ->disabled(fn ($context) => request()->query('orden_compra') && $context === 'create')
                                            ->dehydrated()
                                            ->columnSpan(3),

                                        // Serie Comprobante
                                        TextInput::make('ser_comprobante')
                                            ->label('Serie')
                                            ->required()
                                            ->maxLength(7)
                                            ->placeholder('001-003')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state) {
                                                // Validar formato en tiempo real
                                                if ($state && !preg_match('/^\d{3}-\d{3}$/', $state)) {
                                                    Notification::make()
                                                        ->title('⚠️ Formato de serie incorrecto')
                                                        ->body('La serie debe tener el formato: 001-003 (3 dígitos - guion - 3 dígitos)')
                                                        ->danger()
                                                        ->persistent()
                                                        ->send();
                                                }
                                            })
                                            ->rules([
                                                'required',
                                                'regex:/^\d{3}-\d{3}$/',
                                            ])
                                            ->validationMessages([
                                                'regex' => '⚠️ La serie debe tener el formato: 001-003 (3 dígitos - guion - 3 dígitos)',
                                                'required' => 'La serie de la factura es obligatoria.',
                                            ])
                                            ->helperText('Formato: 001-003')
                                            ->columnSpan(1),

                                        // Número Comprobante
                                        TextInput::make('nro_comprobante')
                                            ->label('Nro. Factura')
                                            ->required()
                                            ->maxLength(7)
                                            ->numeric()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                if (!$state || !$get('cod_proveedor') || !$get('ser_comprobante')) {
                                                    return;
                                                }

                                                // Buscar timbrado válido
                                                $timbrado = DB::table('timbrado_proveedor')
                                                    ->where('cod_proveedor', $get('cod_proveedor'))
                                                    ->where('ser_timbrado', $get('ser_comprobante'))
                                                    ->where('ind_activo', true)
                                                    ->where('fec_vencimiento', '>=', now()->format('Y-m-d'))
                                                    ->first();

                                                if ($timbrado) {
                                                    // Verificar rango
                                                    $nroFactura = (int) $state;
                                                    if ($nroFactura >= $timbrado->numero_inicial && $nroFactura <= $timbrado->numero_final) {
                                                        $set('timbrado', $timbrado->num_timbrado);
                                                        
                                                        Notification::make()
                                                            ->title('✅ Timbrado cargado')
                                                            ->body("Timbrado: {$timbrado->num_timbrado} (Rango: {$timbrado->numero_inicial}-{$timbrado->numero_final})")
                                                            ->success()
                                                            ->duration(3000)
                                                            ->send();
                                                    }
                                                } else {
                                                    // No existe timbrado válido - abrir modal automáticamente
                                                    $set('timbrado', null);
                                                    
                                                    Notification::make()
                                                        ->title('⚠️ Timbrado no encontrado')
                                                        ->body(new \Illuminate\Support\HtmlString('
                                                            Abriendo formulario para registrar nuevo timbrado...
                                                            <script>
                                                                setTimeout(function() {
                                                                    let button = document.querySelector(\'button[wire\\\\:click*="mountFormComponentAction"][wire\\\\:click*="timbrado"][wire\\\\:click*="crearTimbrado"]\');
                                                                    if (button) {
                                                                        button.click();
                                                                    }
                                                                }, 100);
                                                            </script>
                                                        '))
                                                        ->warning()
                                                        ->duration(2000)
                                                        ->send();
                                                }
                                            })
                                            //->helperText('Número')
                                            ->rules([
                                                fn (callable $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                                    // Verificar duplicado
                                                    $existe = DB::table('cm_compras_cabecera')
                                                        ->where('cod_proveedor', $get('cod_proveedor'))
                                                        ->where('tip_comprobante', $get('tip_comprobante'))
                                                        ->where('ser_comprobante', $get('ser_comprobante'))
                                                        ->where('nro_comprobante', $value)
                                                        ->when($get('id_compra_cabecera'), fn ($query, $id) => $query->where('id_compra_cabecera', '!=', $id))
                                                        ->exists();

                                                    if ($existe) {
                                                        // Obtener nombre del proveedor para el mensaje
                                                        $proveedor = \App\Models\Proveedor::with('personas_pro')->find($get('cod_proveedor'));
                                                        $nombreProveedor = $proveedor ? ($proveedor->personas_pro->nombre_completo ?? $proveedor->razon_social ?? 'N/A') : 'N/A';
                                                        
                                                        $fail("⚠️ La factura {$get('ser_comprobante')}-{$value} del proveedor {$nombreProveedor} ya está registrada en el sistema.");
                                                        
                                                        // Mostrar notificación adicional
                                                        Notification::make()
                                                            ->title('❌ Factura Duplicada')
                                                            ->body("La factura **{$get('ser_comprobante')}-{$value}** del proveedor **{$nombreProveedor}** ya existe en el sistema. No se puede registrar dos veces.")
                                                            ->danger()
                                                            ->persistent()
                                                            ->send();
                                                        
                                                        return;
                                                    }

                                                    // Verificar rango de timbrado
                                                    if ($get('cod_proveedor') && $get('ser_comprobante')) {
                                                        $timbrado = DB::table('timbrado_proveedor')
                                                            ->where('cod_proveedor', $get('cod_proveedor'))
                                                            ->where('ser_timbrado', $get('ser_comprobante'))
                                                            ->where('ind_activo', true)
                                                            ->where('fec_vencimiento', '>=', now()->format('Y-m-d'))
                                                            ->first();

                                                        if (!$timbrado) {
                                                            $fail("⚠️ No existe timbrado válido. Use el botón [+] junto al campo Timbrado.");
                                                            return;
                                                        }

                                                        $nroFactura = (int) $value;
                                                        if ($nroFactura < $timbrado->numero_inicial || $nroFactura > $timbrado->numero_final) {
                                                            $fail("⚠️ El número debe estar entre {$timbrado->numero_inicial} y {$timbrado->numero_final}");
                                                        }
                                                    }
                                                },
                                            ])
                                            ->columnSpan(1),
                                    ]),

                                // Grid para Timbrado y Orden de Compra
                                Forms\Components\Grid::make(5)
                                    ->schema([
                                        // Timbrado
                                        TextInput::make('timbrado')
                                            ->label('Timbrado')
                                            ->required()
                                            ->numeric()
                                            ->maxLength(8)
                                            ->validationMessages([
                                                'max' => 'El timbrado no debe tener más de 8 dígitos.',
                                            ])
                                            ->rules(['regex:/^\d{1,8}$/'])
                                            ->helperText('Hasta 8 dígitos')
                                            ->suffixAction(
                                                Action::make('crearTimbrado')
                                                    ->icon('heroicon-o-plus-circle')
                                                    ->tooltip('Registrar nuevo timbrado')
                                                    ->modalHeading('📋 Registrar Nuevo Timbrado')
                                                    ->modalDescription(function (Forms\Get $get) {
                                                        $proveedor = \App\Models\Proveedor::with('personas_pro')->find($get('cod_proveedor'));
                                                        $nombreProveedor = $proveedor ? $proveedor->personas_pro->nombre_completo : 'No seleccionado';
                                                        return "Proveedor: {$nombreProveedor} | Complete los datos del nuevo timbrado";
                                                    })
                                                    ->modalWidth('2xl')
                                                    ->fillForm(function (Forms\Get $get) {
                                                        return [
                                                            'ser_timbrado_nuevo' => $get('ser_comprobante'),
                                                        ];
                                                    })
                                                    ->form([
                                                        Forms\Components\Grid::make(2)
                                                            ->schema([
                                                                Forms\Components\TextInput::make('num_timbrado_nuevo')
                                                                    ->label('Número de Timbrado')
                                                                    ->required()
                                                                    ->numeric()
                                                                    ->maxLength(8)
                                                                    ->placeholder('12345678')
                                                                    ->columnSpan(1),
                                                                
                                                                Forms\Components\TextInput::make('ser_timbrado_nuevo')
                                                                    ->label('Serie del Timbrado')
                                                                    ->required()
                                                                    ->maxLength(7)
                                                                    ->placeholder('001-003')
                                                                    ->live(onBlur: true)
                                                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                                        // Validar formato en tiempo real
                                                                        if ($state && !preg_match('/^\d{3}-\d{3}$/', $state)) {
                                                                            Notification::make()
                                                                                ->title('⚠️ Formato incorrecto')
                                                                                ->body('La serie debe tener el formato: 001-003 (3 dígitos - guion - 3 dígitos)')
                                                                                ->danger()
                                                                                ->duration(5000)
                                                                                ->send();
                                                                        }
                                                                    })
                                                                    ->rules([
                                                                        'required',
                                                                        'regex:/^\d{3}-\d{3}$/',
                                                                    ])
                                                                    ->validationMessages([
                                                                        'regex' => '⚠️ La serie debe tener el formato: 001-003 (3 dígitos - guion - 3 dígitos)',
                                                                        'required' => 'La serie del timbrado es obligatoria.',
                                                                    ])
                                                                    ->helperText('Formato requerido: 001-003')
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
                                                    ->action(function (array $data, Forms\Get $get, Forms\Set $set) {
                                                        $codProveedor = $get('cod_proveedor');
                                                        
                                                        if (!$codProveedor) {
                                                            Notification::make()
                                                                ->title('Error')
                                                                ->body('Debe seleccionar un proveedor primero.')
                                                                ->danger()
                                                                ->send();
                                                            return;
                                                        }

                                                        // Validar formato de serie antes de guardar
                                                        if (!preg_match('/^\d{3}-\d{3}$/', $data['ser_timbrado_nuevo'])) {
                                                            Notification::make()
                                                                ->title('⚠️ Formato de serie incorrecto')
                                                                ->body('La serie debe tener el formato: 001-003 (3 dígitos - guion - 3 dígitos). No se puede guardar el timbrado.')
                                                                ->danger()
                                                                ->persistent()
                                                                ->send();
                                                            return;
                                                        }

                                                        try {
                                                            // Insertar el nuevo timbrado usando el modelo
                                                            $nuevoTimbrado = \App\Models\timbradoProv::create([
                                                                'cod_proveedor' => $codProveedor,
                                                                'num_timbrado' => $data['num_timbrado_nuevo'],
                                                                'ser_timbrado' => $data['ser_timbrado_nuevo'],
                                                                'fecha_inicial' => $data['fecha_inicial_nuevo'],
                                                                'fec_vencimiento' => $data['fec_vencimiento_nuevo'],
                                                                'numero_inicial' => $data['numero_inicial_nuevo'],
                                                                'numero_final' => $data['numero_final_nuevo'],
                                                                'ind_activo' => $data['ind_activo_nuevo'] ?? true,
                                                            ]);

                                                            // Cargar el timbrado en el campo
                                                            $set('timbrado', $data['num_timbrado_nuevo']);
                                                            $set('ser_comprobante', $data['ser_timbrado_nuevo']);

                                                            Notification::make()
                                                                ->title('Timbrado registrado exitosamente')
                                                                ->body("Timbrado {$data['num_timbrado_nuevo']} creado y cargado. (ID: {$nuevoTimbrado->cod_timbrado})")
                                                                ->success()
                                                                ->duration(5000)
                                                                ->send();
                                                        } catch (\Exception $e) {
                                                            Notification::make()
                                                                ->title('Error al registrar timbrado')
                                                                ->body($e->getMessage())
                                                                ->danger()
                                                                ->send();
                                                        }
                                                    })
                                            )
                                            ->columnSpan(2),

                                        // Nro OC Referencia - SOLO SI VIENE DESDE OC
                                        Select::make('nro_oc_ref')
                                            ->label('Orden de Compra Referencia')
                                            ->options(function (Get $get) {
                                                $codProveedor = $get('cod_proveedor');
                                                if (!$codProveedor) {
                                                    return [];
                                                }
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
                                            ->getOptionLabelUsing(function ($value) {
                                                if (!$value) return null;
                                                $orden = OrdenCompraCabecera::find($value);
                                                if (!$orden) return $value;
                                                $label = 'OC Nro. ' . $orden->nro_orden_compra;
                                                if ($orden->fec_orden) {
                                                    $fecha = $orden->fec_orden instanceof \Carbon\Carbon
                                                        ? $orden->fec_orden->format('d/m/Y')
                                                        : $orden->fec_orden;
                                                    $label .= ' — ' . $fecha;
                                                }
                                                return $label;
                                            })
                                            ->live()
                                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                                if (!$state) {
                                                    $set('detalles', []);
                                                    return;
                                                }

                                                $ordenCompra = OrdenCompraCabecera::with('ordenCompraDetalles.articulo')
                                                    ->find($state);

                                                if (!$ordenCompra) {
                                                    return;
                                                }

                                                $detalles = $ordenCompra->ordenCompraDetalles->map(function ($detalle) {
                                                    $cantidad = (float) $detalle->cantidad;
                                                    $precio = (float) $detalle->precio;
                                                    $totalConIva = $cantidad * $precio; // El precio incluye IVA
                                                    $iva = $totalConIva / 11; // Extraer IVA del total (10%)

                                                    return [
                                                        'cod_articulo' => $detalle->cod_articulo,
                                                        'cantidad' => $cantidad,
                                                        'precio_unitario' => $precio,
                                                        'porcentaje_iva' => 10,
                                                        'total_iva' => number_format($iva, 2, '.', ''),
                                                        'monto_total_linea' => number_format($totalConIva, 2, '.', ''),
                                                    ];
                                                })->toArray();

                                                $set('detalles', $detalles);

                                                Notification::make()
                                                    ->title('Detalles cargados desde OC')
                                                    ->success()
                                                    ->send();
                                            })
                                            ->placeholder('Seleccione una orden de compra')
                                            ->helperText('Cargue artículos desde una OC aprobada')
                                            ->visible(fn ($context, Get $get) => request()->query('orden_compra') || $get('nro_oc_ref') || $context !== 'create')
                                            ->disabled(fn ($context) => request()->query('orden_compra') && $context === 'create')
                                            ->dehydrated()
                                            ->columnSpan(2),
                                        
                                        // Impuesto
                                        /*Select::make('cod_impuesto')
                                            ->label('Impuesto')
                                            ->options(function () {
                                                return \App\Models\Impuesto::where('activo', true)
                                                    ->get()
                                                    ->mapWithKeys(function ($impuesto) {
                                                        return [$impuesto->cod_impuesto => $impuesto->descripcion]; 
                                                    });
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->native(false)
                                            ->columnSpan(1),*/
                                    ]),
                            ]),
                        
                        // Sección derecha con Fecha de Vencimiento
                        Section::make('Vencimiento')
                            ->columnSpan(1)
                            ->schema([
                                DatePicker::make('fec_vencimiento')
                                    ->label(function (Forms\Get $get) {
                                        $condicionId = $get('cod_condicion_compra');
                                        if ($condicionId) {
                                            $condicion = \App\Models\CondicionCompra::find($condicionId);
                                            if ($condicion && $condicion->cant_cuota > 0) {
                                                return "Cuota 1 - Fecha Vencimiento";
                                            }
                                        }
                                        return 'Fecha de Vencimiento';
                                    })
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->helperText(function (Forms\Get $get) {
                                        $condicionId = $get('cod_condicion_compra');
                                        if ($condicionId) {
                                            $condicion = \App\Models\CondicionCompra::find($condicionId);
                                            if ($condicion) {
                                                return "Condición: {$condicion->descripcion}";
                                            }
                                        }
                                        return 'Fecha de vencimiento de pago';
                                    }),
                                
                                // Observaciones en el mismo panel
                                Textarea::make('observacion')
                                    ->label('Observaciones')
                                    ->rows(6)
                                    ->maxLength(255)
                                    ->placeholder('Observaciones adicionales...')
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // SECCIÓN 2: Timbrado y Numeración
                /*Section::make('Timbrado y Numeración')
                    ->description('Datos del comprobante físico')
                    ->collapsible()
                    ->columns(4)
                    ->schema([
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
                            ->helperText('Hasta 7 dígitos')
                            ->columnSpan(1),

                        // Usuario Alta (hidden)
                        TextInput::make('usuario_alta')
                            ->label('Usuario')
                            ->default(fn () => auth()->user()->name ?? 'Sistema')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->hidden(),

                        // Fecha Alta (hidden)
                        TextInput::make('fecha_alta')
                            ->label('Fecha Alta')
                            ->default(Carbon::now()->toDateTimeString())
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->hidden(),
                    ]),*/

                // SECCIÓN 3: Detalle de Compra
                Section::make('Artículos y Detalles de la Compra')
                    ->description('Agregue los artículos comprados con sus cantidades y precios')
                    ->collapsed(fn ($context) => $context !== 'create')
                    ->schema([
                        Repeater::make('detalles')
                            ->schema([
                                // Artículo
                                Select::make('cod_articulo')
                                    ->label('Artículo')
                                    ->options(\App\Models\Articulos::pluck('descripcion', 'cod_articulo'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpan(3)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $articulo = Articulos::find($state);
                                            if ($articulo) {
                                                $precio = (float) $articulo->precio;
                                                $set('precio_unitario', $precio);
                                                $cantidad = 1;
                                                $totalConIva = $cantidad * $precio; // El precio YA incluye IVA
                                                $iva = $totalConIva / 11; // Extraer el 10% del total (total/11 para IVA 10%)
                                                $set('monto_total_linea', number_format($totalConIva, 2, '.', ''));
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
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $cantidad = (float) $state;
                                        $precio = (float) $get('precio_unitario');
                                        $totalConIva = $cantidad * $precio; // El precio incluye IVA
                                        $iva = $totalConIva / 11; // Extraer IVA del total (10%)
                                        $set('monto_total_linea', number_format($totalConIva, 2, '.', ''));
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
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $cantidad = (float) ($get('cantidad') ?? 1);
                                        $precio = (float) $state;
                                        $totalConIva = $cantidad * $precio; // El precio incluye IVA
                                        $iva = $totalConIva / 11; // Extraer IVA del total (10%)
                                        $set('monto_total_linea', number_format($totalConIva, 2, '.', ''));
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
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $cantidad = (float) ($get('cantidad') ?? 1);
                                        $precio = (float) ($get('precio_unitario') ?? 0);
                                        $porcentajeIva = (float) $state;
                                        $totalConIva = $cantidad * $precio; // El precio incluye IVA
                                        
                                        // Calcular IVA según porcentaje
                                        if ($porcentajeIva == 10) {
                                            $iva = $totalConIva / 11; // Para 10%: total/11
                                        } elseif ($porcentajeIva == 5) {
                                            $iva = $totalConIva / 21; // Para 5%: total/21
                                        } else {
                                            $iva = $totalConIva * ($porcentajeIva / (100 + $porcentajeIva));
                                        }
                                        
                                        $set('monto_total_linea', number_format($totalConIva, 2, '.', ''));
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
                                    ->live()
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
                            ->live(onBlur: false)
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                // Forzar actualización de los campos de totales
                                // Los Placeholders se actualizarán automáticamente al cambiar el estado
                            })
                            ->grid(1)
                    ]),

                // SECCIÓN 4: Información del Sistema
                Section::make('Información del Sistema')
                    ->description('Usuario y fecha de registro')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('usuario_alta')
                                    ->label('Usuario de Alta')
                                    ->default(fn () => auth()->user()->name ?? 'Sistema')
                                    ->disabled()
                                    ->dehydrated(false),
                                
                                Forms\Components\TextInput::make('fecha_alta_display')
                                    ->label('Fecha de Alta')
                                    ->default(fn () => now()->format('d/m/Y H:i:s'))
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(),

                // SECCIÓN 5: Totales de la Factura
                Section::make('Totales de la Factura')
                    ->description('Resumen de montos')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('total_gravada_calc')
                                    ->label('Total Gravado')
                                    ->content(function (Forms\Get $get): string {
                                        $detalles = $get('detalles') ?? [];
                                        $totalGravado = 0;
                                        
                                        foreach ($detalles as $detalle) {
                                            $totalConIva = isset($detalle['monto_total_linea']) 
                                                ? (float) str_replace(',', '', $detalle['monto_total_linea']) 
                                                : 0;
                                            $iva = isset($detalle['total_iva']) 
                                                ? (float) str_replace(',', '', $detalle['total_iva']) 
                                                : 0;
                                            
                                            // Gravado = Total - IVA
                                            $totalGravado += ($totalConIva - $iva);
                                        }
                                        
                                        return '₲ ' . number_format($totalGravado, 0, ',', '.');
                                    })
                                    ->extraAttributes(['class' => 'text-lg font-bold']),

                                Forms\Components\Placeholder::make('tot_iva_calc')
                                    ->label('Total IVA (10%)')
                                    ->content(function (Forms\Get $get): string {
                                        $detalles = $get('detalles') ?? [];
                                        $totalIva = 0;
                                        
                                        foreach ($detalles as $detalle) {
                                            if (isset($detalle['total_iva'])) {
                                                $totalIva += (float) str_replace(',', '', $detalle['total_iva']);
                                            }
                                        }
                                        
                                        return '₲ ' . number_format($totalIva, 0, ',', '.');
                                    })
                                    ->extraAttributes(['class' => 'text-lg font-bold text-warning-600']),

                                Forms\Components\Placeholder::make('total_general_calc')
                                    ->label('Total General')
                                    ->content(function (Forms\Get $get): string {
                                        $detalles = $get('detalles') ?? [];
                                        $totalGeneral = 0;
                                        
                                        foreach ($detalles as $detalle) {
                                            if (isset($detalle['monto_total_linea'])) {
                                                // Total General = suma de precios con IVA incluido
                                                $totalGeneral += (float) str_replace(',', '', $detalle['monto_total_linea']);
                                            }
                                        }
                                        
                                        return '₲ ' . number_format($totalGeneral, 0, ',', '.');
                                    })
                                    ->extraAttributes(['class' => 'text-xl font-bold text-success-600']),
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

                Tables\Columns\TextColumn::make('confirma')
                    ->label('Confirmado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'S' => 'Confirmado',
                        'N' => 'Pendiente',
                        default => 'Pendiente',
                    })
                    ->color(fn ($state) => match ($state) {
                        'S' => 'success',
                        'N' => 'warning',
                        default => 'gray',
                    }),

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
                                ->warning()
                                ->send();
                        })
                        ->visible(fn (CompraCabecera $record) => $record->estado !== 'Anulado'),

                    Tables\Actions\Action::make('confirmar_factura')
                        ->label('Confirmar Factura')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Confirmar Factura')
                        ->modalDescription('¿Está seguro de confirmar esta factura?')
                        ->modalSubmitActionLabel('Sí, confirmar')
                        ->action(function (CompraCabecera $record) {
                            $record->update([
                                'confirma' => 'S',
                                'fec_confirma' => now(),
                                'usuario_confirma' => auth()->user()->name ?? 'Sistema',
                            ]);
                            Notification::make()
                                ->title('✅ Factura confirmada')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (CompraCabecera $record) => $record->confirma !== 'S'),
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
