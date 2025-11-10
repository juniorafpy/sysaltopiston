<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\OrdenCompraCabecera;
use App\Models\PresupuestoCabecera;
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
use Filament\Forms\Set; // ¡IMPORTANTE! Para cargar datos

// Componentes de Filament
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


            // --- ¡SECCIÓN MODIFICADA! ---
            Section::make('Datos Principales')
                ->schema([
                    // Usamos un Grid de 5 columnas para tener más control
                    Grid::make(['default' => 5])
                        ->schema([

                            // 1. Nro. Presupuesto (Más chico)
                            TextInput::make('nro_presupuesto_ref')
                                ->label('Nro. Presupuesto')
                                ->required()
                                ->live(onBlur: true)
                                ->afterStateUpdated(function ($state, Set $set) {
                                    // ... (Tu lógica para cargar el presupuesto)
                                })
                                ->columnSpan(1), // Ocupa 1 de 5 columnas

                                 Forms\Components\Hidden::make('cod_sucursal'),
                         Forms\Components\TextInput::make('nombre_sucursal')
                ->label('Sucursal')
                ->disabled()
                 ->columnSpan(1)
                ->dehydrated(false),


                         Forms\Components\TextInput::make('nombre_sucursal')
                ->label('Usuario Alta')
                ->disabled()
                 ->columnSpan(1)
                ->dehydrated(false),
                 Forms\Components\TextInput::make('nombre_sucursal')
                ->label('Fecha Alta')
                ->disabled()
                 ->columnSpan(1)
                ->dehydrated(false),


                            // 2. Proveedor (Más grande)
                            Select::make('cod_proveedor')
                                ->relationship('proveedor', 'razon_social')
                                ->searchable()
                                ->required()
                                ->label('Proveedor')
                                ->columnSpan(2), // Ocupa 2 de 5 columnas

                            // 3. Condición de Compra (Más grande)
                            Select::make('cod_condicion_compra')
                                ->relationship('condicionCompra', 'descripcion')
                                ->required()
                                ->label('Condición de Compra')
                                ->columnSpan(1), // Ocupa 2 de 5 columnas
                                                                 DatePicker::make('fec_orden')
                                        ->label('Fecha de Orden')
                                        ->default(now())
                                        ->required(),


                                    DatePicker::make('fec_entrega')
                                        ->label('Fecha de Entrega')
                                        ->required(),

                                    Textarea::make('observacion')
                                        ->label('Observación')
                                        ->columnSpanFull(),

                        ]),
                ]),

            // --- ¡GRID PARA DIVIDIR LAS SIGUIENTES SECCIONES! ---
            Grid::make(['Default' => 5])
                ->schema([

                    // --- Columna Izquierda (Datos de la Orden) ---

                        //->columnSpan(['lg' => 2]), // Ocupa 2/3 del ancho


                    // --- Columna Derecha ("Cuadrito" de Sistema) ---
                    Group::make()
                        ->schema([
                            Section::make('Información del Sistema')
                                ->schema([
                                    TextInput::make('usuarioAlta.name')
                                        ->label('Creado por')
                                        ->disabled(),

                                    TextInput::make('fec_alta')
                                        ->label('Fecha de Creación')
                                        ->disabled(),
                                ])
                                ->hiddenOn('create'),
                        ])
                        ->columnSpan(['lg' => 1]), // Ocupa 1/3 del ancho
                ]),

            // --- Sección de Detalles (abajo) ---
            Section::make('Detalles de la Orden')
                ->schema([

                    Repeater::make('ordenCompraDetalles')
                        ->relationship()
                        // ... (El resto de la configuración de tu Repeater)
                        // ... (Esto se queda igual)
                        ->schema([
                            Select::make('cod_producto')
                                ->relationship('producto', 'nombre')
                                ->searchable()
                                ->required()
                                ->label('Producto')
                                ->columnSpan(2),

                            TextInput::make('cantidad')
                                ->numeric()
                                ->required()
                                ->default(1),

                            TextInput::make('precio_unitario')
                                ->numeric()
                                ->prefix('Gs.')
                                ->required()
                                ->label('Precio Unitario'),
                        ])
                        ->defaultItems(0)
                        ->addActionLabel('Añadir Ítem Manualmente')
                ]),
        ]);
    }

    /**
     * Maneja las 'timestamps' manuales (fec_alta, usuario_alta)
     * ya que tu modelo tiene $timestamps = false;
     */
    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        $data['fec_alta'] = now();
        $data['usuario_alta'] = auth()->id(); // Asume que el usuario está logueado

        return $data;
    }

    // Opcional: Si quieres actualizar al editar
    protected static function mutateFormDataBeforeSave(array $data): array
    {
        // Si no tienes un 'usuario_mod' o 'fec_mod', puedes borrar esta función.
        // $data['fec_mod'] = now();
        // $data['usuario_mod'] = auth()->id();
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

        Tables\Actions\Action::make('anular')
            ->label('Anular')
            ->icon('heroicon-m-no-symbol')
            ->color('danger')
            ->requiresConfirmation()
            ->visible(fn (OrdenCompraCabecera $record) => $record->estado !== 'A')
            ->action(fn (OrdenCompraCabecera $record) => $record->update(['estado' => 'A'])),

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
