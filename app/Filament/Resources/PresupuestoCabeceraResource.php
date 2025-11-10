<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use App\Models\Articulos;
use Filament\Tables\Table;
use App\Models\PedidoCabecera;
use Filament\Resources\Resource;
use App\Models\PresupuestoCabecera;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Factories\Relationship;
use App\Filament\Resources\PresupuestoCabeceraResource\Pages;
use App\Filament\Resources\PresupuestoCabeceraResource\RelationManagers;
use App\Models\PedidoCabeceras;

class PresupuestoCabeceraResource extends Resource
{
    protected static ?string $model = PresupuestoCabecera::class;


    protected static ?string $navigationGroup = 'Compras';
    protected static ?string $navigationLabel = 'Presupuesto Compra';

    protected static ?string $title = 'Presupuesto Compra';
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-document-currency-dollar';


public static function form(Form $form): Form
    {
        return $form->schema([
            // PRIMERA SECCIÓN: CABECERA
            Forms\Components\Section::make('Información de Cabecera')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                       /*     Forms\Components\TextInput::make('nro_presupuesto')
                                ->label('Número de Presupuesto')
                                ->placeholder('Se generará automáticame nte')
                                ->readOnly()
                                ->dehydrated(false),*/

                            // Si el nombre visible está en proveedor->personas_pro

                             Forms\Components\Hidden::make('cod_sucursal'),
                         Forms\Components\TextInput::make('nombre_sucursal')
                ->label('Sucursal')
                ->disabled()
                ->dehydrated(false),

                 TextInput::make('usuario_alta')
                        ->disabled()
                        ->label('Usuario Alta'),

                        Placeholder::make('fec_alta_display') // Dale un nombre único que no sea de una columna
                        ->label('Fecha Alta')
                        ->content(function () {
                            // Formateamos la fecha actual de Paraguay como un string
                            return Carbon::now('America/Asuncion')->format('d/m/Y');
                            }),

                            Forms\Components\Select::make('cod_proveedor')
                                ->label('Proveedor')
                                ->relationship('proveedor','id')
                                ->getOptionLabelFromRecordUsing(fn ($record) =>
                                    $record?->personas_pro?->nombres ?? $record?->nombres ?? ''
                                )
                                ->searchable()
                                ->preload()
                                ->required(),

                            Forms\Components\DatePicker::make('fec_presupuesto')
                                ->label('Fecha del Presupuesto')

                                ->required(),

                            Forms\Components\Select::make('cod_condicion_compra')
                                ->label('Condición de Compra')
                                ->relationship('condicionCompra', 'descripcion')
                                ->searchable()
                                ->preload()
                                ->required(),




                            //pone como default 1 = Pendiente
                            Forms\Components\Hidden::make('estado')
                                ->default(1)          // ← Pendiente
                                ->dehydrated(true)    // lo envía al guardar
                                ->visibleOn('create'), // solo en crear

                                // ... dentro del schema de Cabecera (Grid de 3 columnas) agrega:
                        Forms\Components\Select::make('nro_pedido_ref')
                        ->label('Pedido de Referencia')
                        ->relationship('pedido', 'cod_pedido')
                        ->getOptionLabelFromRecordUsing(
                            fn (PedidoCabeceras $record) => // 👈 TIPAR el parámetro
                                ($record->cod_pedido ?? '') .
                                (isset($record->fec_pedido) ? ' — ' . (
                                    $record->fec_pedido instanceof \Carbon\Carbon
                                        ? $record->fec_pedido->format('d/m/Y')
                                        : (string) $record->fec_pedido
                                ) : '')
                        )
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, \Filament\Forms\Get $get) {
                            if (!$state) return;
                            static::cargarDetallesDesdePedido($state, $set, $get);
                        }),


                        Textarea::make('Observacion')->maxLength(500) ->columnSpan(3),

                    /*    Forms\Components\Select::make('cod_estado')
                        ->label('Estado')
                        ->relationship('estadoRel', 'descripcion') // define belongsTo en tu modelo
                        ->searchable()
                        ->preload()
                        ->required(),*/



                            // Estos los seteamos SOLO en Create vía hook (ver sección 2)
                            Forms\Components\Hidden::make('cod_sucursal')->visibleOn('create'),
                            Forms\Components\Hidden::make('usuario_alta')->visibleOn('create'),
                            Forms\Components\Hidden::make('fec_alta')->visibleOn('create'),
                            Forms\Components\Hidden::make('cargado')->default('N')->visibleOn('create'),
                        ]),
                ]),

            // SEGUNDA SECCIÓN: DETALLES
            Forms\Components\Section::make('Detalles del Presupuesto')
                ->schema([
                    Forms\Components\Repeater::make('presupuestoDetalles')
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
                        ->afterStateUpdated(function (Get $get, Set $set) {
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
                        ),
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


    protected static function cargarDetallesDesdePedido(int|string $codPedido, Set $set, Get $get): void
{
    $pedido = PedidoCabeceras::with(['detalles', 'detalles.articulo'])
        ->where('cod_pedido', $codPedido) // 👈 columna correcta
        ->first();

    if (!$pedido) {
        // Si querés limpiar cuando no existe:
        // $set('presupuestoDetalles', []);
        return;
    }

    $items = [];
    foreach ($pedido->detalles as $d) {
        $cantidad = (float) ($d->cantidad ?? 0);
        $precio   = (float) ($d->precio ?? 0);
        $exenta   = (float) ($d->exenta ?? 0);
        $total    = $cantidad * $precio;
        $iva      = max(0, ($total - $exenta)) * 0.10;

        $items[] = [
            'cod_articulo' => $d->cod_articulo,
            // si usás Hidden('descripcion') para el itemLabel del repeater:
            'descripcion'  => $d->articulo->descripcion ?? ($d->descripcion ?? ''),
            'precio'       => $precio,
             'cantidad'     => $cantidad,
            'Porc Impuesto' => 10,
            'Monto Impuesto',
           // 'exenta'       => $exenta,
            'total'        => number_format($total, 2, '.', ''),
            'total_iva'    => number_format($iva, 2, '.', ''),
        ];
    }

    // Reemplaza el contenido del Repeater por los ítems del pedido:
    $set('presupuestoDetalles', $items);

    // Recalcular totales de cabecera
    static::recalcularTotalesCabecera($get, $set);
}

protected static function recalcularTotalesCabecera(Get $get, Set $set): void
{
    $detalles = $get('presupuestoDetalles') ?? [];
    $grav = 0.0; $iva = 0.0;

    foreach ($detalles as $d) {
        $grav += (float) str_replace(',', '', $d['total'] ?? 0);
        $iva  += (float) str_replace(',', '', $d['total_iva'] ?? 0);
    }

    $set('total_gravada', number_format($grav, 2, '.', ''));
    $set('tot_iva', number_format($iva, 2, '.', ''));
    $set('total_general', number_format($grav + $iva, 2, '.', ''));
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nro_presupuesto')
                    ->numeric()
                    ->label('Nro.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('proveedor.personas_pro.nombre_completo')
                 ->label('Proveedor')
                 ->searchable(),
                   // ->sortable(),
                Tables\Columns\TextColumn::make('fec_presupuesto')
                    ->date('d/m/Y'),
                    //->sortable(),

                Tables\Columns\TextColumn::make('condicionCompra.descripcion')
                 ->label('Condicion'),
                   // ->searchable(),

                Tables\Columns\TextColumn::make('estadoRel.descripcion')
                ->label('Estado')
                ->sortable(),

            ])

        // 👇 Título de la columna de acciones
        ->actionsColumnLabel('Acciones')

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
            ->requiresConfirmation()
            ->visible(fn (PresupuestoCabecera $record) => $record->estado !== 'A')
            ->action(fn (PresupuestoCabecera $record) => $record->update(['estado' => 'A'])),

        Tables\Actions\Action::make('aprobar')
            ->label('Aprobar')
            ->icon('heroicon-m-no-symbol')
            ->color('success')
            ->requiresConfirmation(),
            //->visible(fn (PresupuestoCabecera $record) => $record->estado !== 'A')
           // ->action(fn (PresupuestoCabecera $record) => $record->update(['estado' => 'A'])),
    ])
        ->label('Opciones')                      // texto del botón (opcional)
        ->icon('heroicon-m-ellipsis-vertical'),  // ícono de “tres puntitos”
    ]);



    }


    public static function getRelations(): array
    {
        return [
            //RelationManagers\PresupuestoDetallesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPresupuestoCabeceras::route('/'),
            'create' => Pages\CreatePresupuestoCabecera::route('/create'),
            'edit' => Pages\EditPresupuestoCabecera::route('/{record}/edit'),
        ];
    }
}
