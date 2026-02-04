<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\OrdenCompraCabecera;
use App\Models\PresupuestoCabecera;
use App\Models\ExisteStock;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Set;
use Filament\Forms\Get;
use App\Filament\Resources\OrdenCompraCabeceraResource\Pages;
use App\Filament\Resources\OrdenCompraCabeceraResource\RelationManagers;

class OrdenCompraCabeceraResource extends Resource
{
    protected static ?string $model = OrdenCompraCabecera::class;

    // Ajusta el ícono y el nombre
     protected static ?string $navigationGroup = 'Compras';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $modelLabel = 'Orden de Compra';
    protected static ?string $pluralModelLabel = 'Ordenes de Compra';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([


            // --- Sección de Datos Principales ---
            Section::make('Datos Principales')
                ->schema([
                    Grid::make(4)
                        ->schema([
                            // Proveedor - Movido al principio para que se seleccione primero
                            Select::make('cod_proveedor')
                                ->label('Proveedor')
                                ->relationship('proveedor', 'cod_proveedor')
                                ->getOptionLabelFromRecordUsing(fn ($record) =>
                                    $record?->personas_pro?->nombre_completo ?? $record?->razon_social ?? ''
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    // Limpiar presupuesto cuando cambia el proveedor
                                    $set('nro_presupuesto_ref', null);
                                })
                                ->disabled(fn (Get $get) => $get('nro_presupuesto_ref') !== null)
                                ->dehydrated()
                                ->columnSpan(2),

                            // Nro. Presupuesto - Filtrado por proveedor seleccionado
                            Select::make('nro_presupuesto_ref')
                                ->label('Presupuesto de Referencia')
                                ->options(function ($record, Get $get) {
                                    $codProveedor = $get('cod_proveedor');

                                    // Si no hay proveedor seleccionado, no mostrar presupuestos
                                    if (!$codProveedor) {
                                        return [];
                                    }

                                    // Solo presupuestos APROBADOS que NO estén cargados en ninguna orden de compra
                                    $presupuestosYaCargados = OrdenCompraCabecera::whereNotNull('nro_presupuesto_ref')
                                        ->when($record, function ($query) use ($record) {
                                            // Si estamos editando, excluir el presupuesto actual de la lista de "ya cargados"
                                            $query->where('nro_orden_compra', '!=', $record->nro_orden_compra);
                                        })
                                        ->pluck('nro_presupuesto_ref')
                                        ->toArray();

                                    return PresupuestoCabecera::where('estado', 'APROBADO')
                                        ->where('cod_proveedor', $codProveedor) // Filtrar por proveedor
                                        ->whereNotIn('nro_presupuesto', $presupuestosYaCargados)
                                        ->get()
                                        ->mapWithKeys(function ($presupuesto) {
                                            $label = 'Nro. ' . $presupuesto->nro_presupuesto;
                                            if ($presupuesto->fec_presupuesto) {
                                                $fecha = $presupuesto->fec_presupuesto instanceof \Carbon\Carbon
                                                    ? $presupuesto->fec_presupuesto->format('d/m/Y')
                                                    : $presupuesto->fec_presupuesto;
                                                $label .= ' — ' . $fecha;
                                            }
                                            return [$presupuesto->nro_presupuesto => $label];
                                        });
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    if (!$state) return;
                                    static::cargarDetallesDesdePresupuesto($state, $set, $get);
                                })
                                ->disabled(fn ($context) => $context === 'edit')
                                ->placeholder('Primero seleccione un proveedor')
                               // ->helperText('Solo se muestran presupuestos aprobados del proveedor seleccionado')
                                ->columnSpan(1),

                            // Condición de Compra
                            Select::make('cod_condicion_compra')
                                ->relationship('condicionCompra', 'descripcion')
                                ->required()
                                ->label('Condición de Compra')
                                ->disabled(fn (Get $get) => $get('nro_presupuesto_ref') !== null)
                                ->dehydrated()
                                ->columnSpan(1),

                            // Fecha de Orden
                            DatePicker::make('fec_orden')
                                ->label('Fecha de Orden')
                                ->default(now())
                                ->required()
                                ->columnSpan(1),

                            // Fecha de Entrega
                            DatePicker::make('fec_entrega')
                                ->label('Fecha de Entrega')
                                ->required()
                                ->columnSpan(1),

                            // Estado (hidden, se asigna automáticamente)
                            Forms\Components\Hidden::make('estado')
                                ->default(1),

                            // Sucursal (hidden)
                            Forms\Components\Hidden::make('cod_sucursal')
                                ->default(fn () => auth()->user()->cod_sucursal),

                            // Observación
                            Textarea::make('observacion')
                                ->label('Observación')
                                ->columnSpanFull(),
                        ]),
                ]),

            // --- Información del Sistema ---
            Section::make('Información del Sistema')
                ->schema([
                    Grid::make(4)
                        ->schema([
                            TextInput::make('usuarioAlta.name')
                                ->label('Creado por')
                                ->disabled()
                                ->columnSpan(1),

                            TextInput::make('fec_alta')
                                ->label('Fecha de Creación')
                                ->disabled()
                                ->columnSpan(1),

                            TextInput::make('usuario_modifica')
                                ->label('Modificado por')
                                ->disabled()
                                ->columnSpan(1),

                            TextInput::make('fec_modifica')
                                ->label('Fecha de Modificación')
                                ->disabled()
                                ->columnSpan(1),
                        ]),
                ])
                ->hiddenOn('create')
                ->collapsed(),

            // --- Sección de Detalles (abajo) ---
            Section::make('Detalles de la Orden')
                ->schema([

                    Repeater::make('ordenCompraDetalles')
                        ->relationship()
                        ->schema([
                            Select::make('cod_articulo')
                                ->relationship('articulo', 'descripcion')
                                ->searchable()
                                ->required()
                                ->label('Artículo')
                                ->live()
                                ->disabled(fn (Get $get) => $get('../../nro_presupuesto_ref') !== null)
                                ->dehydrated()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if ($state) {
                                        // Obtener el stock disponible del artículo en la sucursal actual
                                        $sucursalId = auth()->user()->cod_sucursal ?? 1;
                                        $stock = \App\Models\ExisteStock::where('cod_articulo', $state)
                                            ->where('cod_sucursal', $sucursalId)
                                            ->first();

                                        if ($stock) {
                                            $stockDisponible = $stock->stock_actual - $stock->stock_reservado;
                                            $set('stock_disponible_display', number_format($stockDisponible, 2));
                                        } else {
                                            $set('stock_disponible_display', '0.00 (Sin stock registrado)');
                                        }
                                    } else {
                                        $set('stock_disponible_display', '');
                                    }
                                })
                                ->placeholder('Seleccione un artículo')
                                ->columnSpan(2),

                            TextInput::make('stock_disponible_display')
                                ->label('Stock Disponible')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('---')
                                ->columnSpan(1),

                            TextInput::make('cantidad')
                                ->numeric()
                                ->required()
                                ->default(1)
                                ->minValue(0.01)
                                ->label('Cantidad')
                                ->live()
                                ->disabled(fn (Get $get) => $get('../../nro_presupuesto_ref') !== null)
                                ->dehydrated()
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    $cantidad = floatval($state ?? 0);
                                    $precio = floatval($get('precio') ?? 0);
                                    $subtotal = $cantidad * $precio;
                                    // IVA 10%
                                    $totalIva = $subtotal * 0.10;
                                    $set('total', $subtotal);
                                    $set('total_iva', $totalIva);
                                })
                                ->columnSpan(1),

                            TextInput::make('precio')
                                ->numeric()
                                ->prefix('Gs.')
                                ->required()
                                ->minValue(0)
                                ->label('Precio Unit.')
                                ->live()
                                ->disabled(fn (Get $get) => $get('../../nro_presupuesto_ref') !== null)
                                ->dehydrated()
                                ->afterStateUpdated(function ($state, Get $get, Set $set) {
                                    $cantidad = floatval($get('cantidad') ?? 0);
                                    $precio = floatval($state ?? 0);
                                    $subtotal = $cantidad * $precio;
                                    // IVA 10%
                                    $totalIva = $subtotal * 0.10;
                                    $set('total', $subtotal);
                                    $set('total_iva', $totalIva);
                                })
                                ->columnSpan(1),

                            TextInput::make('total')
                                ->numeric()
                                ->prefix('Gs.')
                                ->disabled()
                                ->label('Subtotal')
                                ->columnSpan(1)
                                ->dehydrated(),

                            TextInput::make('total_iva')
                                ->numeric()
                                ->prefix('Gs.')
                                ->disabled()
                                ->label('IVA 10%')
                                ->default(0)
                                ->columnSpan(1)
                                ->dehydrated(),
                        ])
                        ->columns(7)
                        ->defaultItems(0)
                        ->addActionLabel('Añadir Artículo')
                        ->addable(fn (Get $get) => $get('nro_presupuesto_ref') === null)
                        ->deletable(fn (Get $get) => $get('nro_presupuesto_ref') === null)
                        ->reorderable(false)
                        ->live(),
                ]),
        ]);
    }

    /**
     * Carga los detalles desde un presupuesto aprobado
     */
    protected static function cargarDetallesDesdePresupuesto(int|string $nroPresupuesto, Set $set, Get $get): void
    {
        $presupuesto = PresupuestoCabecera::with(['presupuestoDetalles', 'presupuestoDetalles.articulo'])
            ->where('nro_presupuesto', $nroPresupuesto)
            ->first();

        if (!$presupuesto) {
            return;
        }

        // Cargar datos de cabecera
        $set('cod_proveedor', $presupuesto->cod_proveedor);
        $set('cod_condicion_compra', $presupuesto->cod_condicion_compra);
        $set('observacion', 'Basado en presupuesto Nro. ' . $nroPresupuesto);

        // Cargar detalles
        $items = [];
        foreach ($presupuesto->presupuestoDetalles as $d) {
            $cantidad = (float) ($d->cantidad ?? 0);
            $precio   = (float) ($d->precio ?? 0);
            $total    = $cantidad * $precio;
            $iva      = $total * 0.10;

            $items[] = [
                'cod_articulo' => $d->cod_articulo,
                'cantidad'     => $cantidad,
                'precio'       => $precio,
                'total'        => $total,
                'total_iva'    => $iva,
            ];
        }

        // Reemplaza el contenido del Repeater
        $set('ordenCompraDetalles', $items);
    }

    /**
     * Maneja las 'timestamps' manuales (fec_alta, usuario_alta)
     * ya que tu modelo tiene $timestamps = false;
     */
    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['fec_alta'] = now();
        $data['usuario_alta'] = auth()->user()->name;
        $data['cod_sucursal'] = $data['cod_sucursal'] ?? auth()->user()->cod_sucursal;
        $data['estado'] = $data['estado'] ?? 1; // Estado default "Pendiente"

        return $data;
    }

    // Opcional: Si quieres actualizar al editar
    protected static function mutateFormDataBeforeSave(array $data): array
    {
        $data['usuario_modifica'] = auth()->user()->name;
        $data['fec_modifica'] = now();

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
               Tables\Columns\TextColumn::make('nro_orden_compra')
                    ->numeric()
                    ->label('Nro.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('proveedor.personas_pro.nombre_completo')
                 ->label('Proveedor')
                 ->searchable(),
                   // ->sortable(),
                Tables\Columns\TextColumn::make('fec_orden')
                    ->date('d/m/Y'),
                    //->sortable(),

                Tables\Columns\TextColumn::make('condicionCompra.descripcion')
                 ->label('Condicion'),
                   // ->searchable(),

                Tables\Columns\TextColumn::make('estadoRel.descripcion')
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

        Tables\Actions\Action::make('imprimir')
            ->label('Imprimir PDF')
            ->icon('heroicon-m-printer')
            ->color('warning')
            ->url(fn (OrdenCompraCabecera $record) => route('orden-compra.pdf', $record))
            ->openUrlInNewTab(),

        Tables\Actions\Action::make('anular')
            ->label('Anular')
            ->icon('heroicon-m-no-symbol')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (OrdenCompraCabecera $record) => $record->estado !== 'A')
            ->action(fn (OrdenCompraCabecera $record) => $record->update(['estado' => 'A'])),

        Tables\Actions\Action::make('aprobar')
            ->label('Aprobar')
            ->icon('heroicon-m-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->visible(fn (OrdenCompraCabecera $record) => $record->estado === 1)
            ->action(fn (OrdenCompraCabecera $record) => $record->update(['estado' => 2])),
    ])
        ->label('Opciones')                      // texto del botón (opcional)
        ->icon('heroicon-m-ellipsis-vertical'),  // ícono de “tres puntitos”
    ]);

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
            'index' => Pages\ListOrdenCompraCabeceras::route('/'),
            'create' => Pages\CreateOrdenCompraCabecera::route('/create'),
            'edit' => Pages\EditOrdenCompraCabecera::route('/{record}/edit'),
        ];
    }
}
