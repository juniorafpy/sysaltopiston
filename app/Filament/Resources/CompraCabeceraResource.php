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

use Ramsey\Collection\Set;
use App\Models\CompraCabecera;
//alex
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Actions\Action;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CompraCabeceraResource\Pages;
use App\Filament\Resources\CompraCabeceraResource\RelationManagers;
use App\Filament\Resources\CompraCabeceraResource\RelationManagers\CompraDetalleRelationManager;

class CompraCabeceraResource extends Resource
{
    protected static ?string $model = CompraCabecera::class;

     protected  static bool $canCreateAnother =  false;


    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationGroup = 'Compras'; // <-- Asegúrate de que tenga un icono
    protected static ?string $navigationLabel = 'Facturas de Compra'; // <-- Opcional, pero útil
    protected static ?string $modelLabel = 'Factura de Compra';

    protected static ?int $navigationSort = 4;




    public static function form(Form $form): Form
    {
        return $form
            ->schema([
            Grid::make(3) // Distribuye los campos en 3 columnas
                ->schema([

                    // 1. Tipo de Comprobante (FAC, CON)
                    Select::make('tip_comprobante')
                        ->label('Tipo Factura')
                        ->options([
                            'FAC' => 'Factura Crédito',
                            'CON' => 'Factura Contado',
                        ])
                        ->required()
                        ->default('FAC')
                        ->columnSpan(1),

                    // 2. Proveedor
                     Forms\Components\Select::make('cod_proveedor')
                                ->label('Proveedor')
                                ->relationship('proveedor','id')
                                ->getOptionLabelFromRecordUsing(fn ($record) =>
                                    $record?->personas_pro?->nombres ?? $record?->nombres ?? ''
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                        ->columnSpan(2),

                    // 3. Sucursal y Fechas
                    TextInput::make('cod_sucursal')
                        ->label('Cód. Sucursal')
                        ->numeric()
                        ->default(1) // Valor por defecto, ajusta según necesidad
                        ->required(),

                        TextInput::make('usuario_alta')
                        ->label('Usuario Alta')

                        ->default('ALEALV') // Valor por defecto, ajusta según necesidad
                        ->required(),

                        TextInput::make('fec_alta')
                        ->label('Fecha Alta')
                       // ->TextInput::make(''),()
                        ->default(Carbon::now()->toDateTimeString()) // Valor por defecto, ajusta según necesidad
                        ->required(),

                    DatePicker::make('fec_comprobante')
                        ->label('Fecha Comprobante')
                        ->required()
                        ->default(now()),

                    DatePicker::make('fec_vencimiento')
                        ->label('Fecha Vencimiento')
                        ->required()
                        ->default(now()->addDays(30)),

                    // 4. Serie y Timbrado (Lógica de Búsqueda)
                   TextInput::make('ser_comprobante')
    ->label('Serie Comprobante')
    ->required()
    ->maxLength(7)
    ->placeholder('Ej: 001-003')
    ->autofocus()
    ->reactive() // Mantenemos reactive para que se dispare al escribir
    ->rules([
        // Esta regla sigue validando el formato en la interfaz
        'regex:/^\d{3}-\d{3}$/',
    ])
    ->afterStateUpdated(function ($state, callable $set, $get) {

        // 💡 CAMBIO CLAVE: Solo ejecuta la lógica si el estado cumple el patrón completo (###-###)
        if (preg_match('/^\d{3}-\d{3}$/', $state) && $get('cod_proveedor')) {

            $timbrado = DB::table('cm_timbrado_prov')
                ->where('cod_proveedor', $get('cod_proveedor'))
                ->where('ser_timbrado', $state)
                ->value('num_timbrado');

            if ($timbrado) {
                $set('timbrado', $timbrado);
            } else {
                $set('timbrado', null);

                // Dispara la notificación solo si la búsqueda no encuentra nada
                Notification::make()
                    ->title('Timbrado no encontrado')
                    ->body("No se encontró el timbrado para la serie **{$state}** del proveedor. Por favor, cárguelo.")
                    ->danger()
                    ->send();
            }
        } else {
            // Si el formato no está completo, limpia el campo 'timbrado' para evitar errores
            // y para que la búsqueda se haga solo al completar
            $set('timbrado', null);
        }
    }),

                    // Campo Timbrado (se llena automáticamente o manualmente)
                    TextInput::make('timbrado')
                        ->label('Timbrado')
                        ->required()
                        ->numeric()
                        ->columnSpan(1)
                        ->suffixAction( // Botón para cargar el timbrado si no existe
                            Action::make('cargar_timbrado')
                                ->icon('heroicon-m-plus')
                                ->label('Cargar')
                                ->visible(fn ($get) => !$get('timbrado')) // Solo visible si 'timbrado' está vacío
                                ->action(function () {
                                    // Aquí puedes abrir un modal o redirigir para cargar el timbrado
                                    \Filament\Notifications\Notification::make()
                                        ->title('Acción de Carga')
                                        ->body('Lógica para cargar nuevo timbrado al proveedor.')
                                        ->info()
                                        ->send();
                                })
                        ),

                    // 5. Número de Comprobante (Validación de Duplicidad)
                    TextInput::make('nro_comprobante')
                        ->label('Nro. Comprobante')
                        ->required()
                        ->maxLength(7) // Asume 7 dígitos para el número
                        ->numeric()
                        ->rules([
                            // Regla de validación personalizada para duplicidad
                            fn (callable $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                $existe = DB::table('cm_compras_cabecera')
                                    ->where('cod_proveedor', $get('cod_proveedor'))
                                    ->where('tip_comprobante', $get('tip_comprobante'))
                                    ->where('ser_comprobante', $get('ser_comprobante'))
                                    ->where('nro_comprobante', $value)
                                    // Excluir el registro actual si estamos editando
                                    ->when($get('id_compra_cabecera'), fn ($query, $id) => $query->where('id_compra_cabecera', '!=', $id))
                                    ->exists();

                                if ($existe) {
                                    $fail("La factura **{$get('ser_comprobante')}-{$value}** del proveedor ya ha sido cargada.");
                                }
                            },
                        ])
                        ->columnSpan(2),

                    // 6. Condición de Compra
                       Forms\Components\Select::make('cod_condicion_compra')
                                ->label('Condición de Compra')
                                ->relationship('condicionCompra', 'descripcion')
                                ->searchable()
                                ->preload()
                                ->required(),

                    // 7. Otros Campos
                    TextInput::make('nro_oc_ref')
                        ->label('Nro. OC Ref.')
                        ->nullable(),

                    TextInput::make('observacion')
                        ->label('Observación')
                        ->maxLength(255)
                        ->nullable()
                        ->columnSpanFull(), // Ocupa todo el ancho

                ]),

                Section::make('Detalle de Factura compra')
    ->schema([
        Repeater::make('detalles')
                        ->relationship() // hasMany presupuestoDetalles()
                        ->schema([
                            Forms\Components\Select::make('cod_articulo')
                                ->label('Artículo')
                                ->relationship('articulo', 'descripcion')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(1)
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if ($state) {
                                        $articulo = Articulos::find($state);
                                        if ($articulo) {
                                            $precio = (float) $articulo->precio;
                                            $set('precio', $precio);
                                            $cantidad = 1;
                                            $total = $cantidad * $precio;
                                            $iva   = max(0, $total) * 0.10;
                                            $set('total', number_format($total, 2, '.', ''));
                                            $set('total_iva', number_format($iva, 2, '.', ''));
                                        }
                                    }
                                }),

                                 Forms\Components\TextInput::make('precio')
                                ->numeric()->minValue(0)->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $cantidad = (float) ($get('cantidad') ?? 0);
                                    $precio   = (float) $state;
                                    $exenta   = (float) ($get('exenta') ?? 0);
                                    $total    = $cantidad * $precio;
                                    $iva      = max(0, ($total - $exenta)) * 0.10;
                                    $set('total', number_format($total, 2, '.', ''));
                                    $set('total_iva', number_format($iva, 2, '.', ''));
                                }),

                            Forms\Components\TextInput::make('cantidad')
                                ->numeric()->minValue(0.01)->default(1)->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $cantidad = (float) $state;
                                    $precio   = (float) $get('precio');
                                    $exenta   = (float) ($get('exenta') ?? 0);
                                    $total    = $cantidad * $precio;
                                    $iva      = max(0, ($total - $exenta)) * 0.10;
                                    $set('total', number_format($total, 2, '.', ''));
                                    $set('total_iva', number_format($iva, 2, '.', ''));
                                }),



                            Forms\Components\TextInput::make('Porc Impuesto')
                                ->numeric()->minValue(0)->default(0)
                                ->reactive()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $cantidad = (float) ($get('cantidad') ?? 0);
                                    $precio   = (float) ($get('precio') ?? 0);
                                    $exenta   = (float) $state;
                                    $total    = $cantidad * $precio;
                                    $iva      = max(0, ($total - $exenta)) * 0.10;
                                    $set('total', number_format($total, 2, '.', ''));
                                    $set('total_iva', number_format($iva, 2, '.', ''));
                                }),

                            Forms\Components\TextInput::make('total_iva')
                                ->label('Total IVA')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(false),

                                 Forms\Components\TextInput::make('Monto Total')
                                ->label('Total')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(false),
                        ])
                        ->columns(6)
                        ->addActionLabel('+ Agregar Artículo')
                        ->defaultItems(0)
                        ->reorderable(false)
                        ->live()
                       /* ->afterStateUpdated(function (Get $get, Set $set) {
                            $detalles = $get('presupuestoDetalles') ?? [];
                            $grav = 0.0; $iva = 0.0;
                            foreach ($detalles as $d) {
                                $grav += (float) str_replace(',', '', $d['total'] ?? 0);
                                $iva  += (float) str_replace(',', '', $d['total_iva'] ?? 0);
                            }
                            $set('total_gravada', number_format($grav, 2, '.', ''));
                            $set('tot_iva', number_format($iva, 2, '.', ''));
                            $set('total_general', number_format($grav + $iva, 2, '.', ''));
                        })
                        ->deleteAction(
                            fn (Forms\Components\Actions\Action $action) => $action->after(
                                fn (Get $get, Set $set) => (function () use ($get, $set) {
                                    $detalles = $get('presupuestoDetalles') ?? [];
                                    $grav = 0.0; $iva = 0.0;
                                    foreach ($detalles as $d) {
                                        $grav += (float) str_replace(',', '', $d['total'] ?? 0);
                                        $iva  += (float) str_replace(',', '', $d['total_iva'] ?? 0);
                                    }
                                    $set('total_gravada', number_format($grav, 2, '.', ''));
                                    $set('tot_iva', number_format($iva, 2, '.', ''));
                                    $set('total_general', number_format($grav + $iva, 2, '.', ''));
                                })()
                            ),
                        ),*/
                ]),

            // TERCERA SECCIÓN: TOTALES
            Forms\Components\Section::make('Totales')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('total_gravada')
                                ->label('Total Gravada')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(false),

                            Forms\Components\TextInput::make('tot_iva')
                                ->label('Total IVA')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(false),

                            Forms\Components\TextInput::make('total_general')
                                ->label('Total General')
                                ->numeric()
                                ->readOnly()
                                ->dehydrated(false),
                        ]),
                ]),

        ]);
        }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                 Tables\Columns\TextColumn::make('id_compra_cabecera')
                    ->numeric()
                    ->label('Nro.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('proveedor.personas_pro.nombre_completo')
                 ->label('Proveedor')
                 ->searchable(),
                   // ->sortable(),
                Tables\Columns\TextColumn::make('fec_comprobante')
                    ->date('d/m/Y'),
                    //->sortable(),

                Tables\Columns\TextColumn::make('condicionCompra.descripcion')
                 ->label('Condicion'),
                   // ->searchable(),

                Tables\Columns\TextColumn::make('estado')
                ->label('Estado')
                ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
              ActionGroup::make([
        Tables\Actions\ViewAction::make()
            ->label('Ver')
            ->color('info')
            ->icon('heroicon-m-eye'),

        Tables\Actions\EditAction::make()
            ->label('Editar')
            ->icon('heroicon-m-pencil-square'),

        Tables\Actions\Action::make('anular')
            ->label('Anular')
            ->icon('heroicon-m-no-symbol')
            ->color('danger')
            ->requiresConfirmation(),
    ])
        ->label('Opciones')                      // texto del botón (opcional)
        ->icon('heroicon-m-ellipsis-vertical'),  // ícono de “tres puntitos”
    ]);

    }

    public static function getRelations(): array
    {
        return [
            CompraDetalleRelationManager::class,
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
