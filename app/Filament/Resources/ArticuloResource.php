<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Articulo;
use Filament\Forms\Form;
use App\Models\Articulos;
use App\Models\Impuesto;
use App\Models\TipoRepuesto;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\ArticuloResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ArticuloResource\RelationManagers;

class ArticuloResource extends Resource
{
    protected static ?string $model = Articulos::class;

    protected static ?string $navigationGroup = 'Definiciones';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $modelLabel = 'Artículo';

    protected static ?string $pluralModelLabel = 'Lista de Artículos';

    protected static ?string $recordTitleAttribute = 'descripcion';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Artículo')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('descripcion')
                                    ->label('Descripción')
                                    ->required()
                                    ->extraInputAttributes([
                                        'oninput' => 'this.value = this.value.toUpperCase()'
                                    ])
                                    ->dehydrateStateUsing(fn ($state) => mb_strtoupper($state ?? '', 'UTF-8'))
                                    ->maxLength(255)
                                    ->placeholder('Ej: Filtro de aceite para motor')
                                    ->columnSpan(2),

                                Toggle::make('activo')
                                    ->label('Activo')
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText('Artículo disponible'),

                                Select::make('cod_marca')
                                    ->label('Marca')
                                    ->options(fn () => \App\Models\Marcas::pluck('descripcion', 'cod_marca'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('descripcion')
                                            ->label('Descripción de la Marca')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $marca = \App\Models\Marcas::create($data);
                                        return $marca->cod_marca;
                                    }),

                                Select::make('cod_modelo')
                                    ->label('Modelo')
                                    ->options(fn () => \App\Models\Modelos::pluck('descripcion', 'cod_modelo'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('descripcion')
                                            ->label('Descripción del Modelo')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $modelo = \App\Models\Modelos::create($data);
                                        return $modelo->cod_modelo;
                                    }),

                               /* Select::make('cod_tip_articulo')
                                    ->label('Tipo de Artículo')
                                    ->options(fn () => \App\Models\TipoArticulos::pluck('descripcion', 'cod_tip_articulo'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),*/

                                Select::make('cod_tipo_repuesto')
                                    ->label('Categoría Repuesto')
                                    ->options(fn () => TipoRepuesto::where('activo', true)
                                        ->pluck('descripcion', 'cod_tipo_repuesto'))
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Categoría específica del repuesto'),

                                Select::make('cod_medida')
                                    ->label('Unidad de Medida')
                                    ->options(fn () => \App\Models\Medidas::pluck('descripcion', 'cod_medida'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Unuidad de medida'),
                            ]),
                    ]),

                Section::make()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('costo')
                                    ->label('Costo')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Gs.')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(1000)
                                    ->helperText('Precio de compra del artículo')
                                    ->live(onBlur: true),

                                TextInput::make('precio')
                                    ->label('Precio de Venta')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Gs.')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(1000)
                                    ->helperText('Precio de venta al público')
                                    ->live(onBlur: true),

                                Forms\Components\Placeholder::make('margen')
                                    ->label('Margen de Ganancia')
                                    ->content(function ($get) {
                                        $costo = floatval($get('costo') ?? 0);
                                        $precio = floatval($get('precio') ?? 0);

                                        if ($costo > 0 && $precio > 0) {
                                            $margen = (($precio - $costo) / $costo) * 100;
                                            return number_format($margen, 2) . '%';
                                        }

                                        return 'N/A';
                                    }),
                            ]),
                        Grid::make(2)
                            ->schema([
                                Select::make('cod_impuesto')
                                    ->label('IVA')
                                    ->options(fn () => Impuesto::where('activo', true)
                                        ->pluck('descripcion', 'cod_impuesto'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->default(1)
                                    ->helperText('Seleccione el tipo de IVA aplicable')
                                    ->live(),

                                Forms\Components\Placeholder::make('porc_iva_display')
                                    ->label('Porcentaje IVA')
                                    ->content(function ($get) {
                                        $codImpuesto = $get('cod_impuesto');
                                        if ($codImpuesto) {
                                            $impuesto = Impuesto::find($codImpuesto);
                                            return $impuesto ? $impuesto->porcentaje . '%' : 'N/A';
                                        }
                                        return 'N/A';
                                    }),
                            ]),
                    ]),

                Section::make()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('usuario_alta')
                                    ->label('Usuario Alta')
                                    ->default(fn () => Auth::user()->name)
                                    ->disabled()
                                    ->dehydrateStateUsing(fn ($state) => $state ?? Auth::user()->name),

                                TextInput::make('fec_alta')
                                    ->label('Fecha Alta')
                                    ->default(fn () => now()->format('d/m/Y H:i'))
                                    ->disabled()
                                    ->dehydrateStateUsing(fn ($state) => now()),

                                TextInput::make('sucursal_usuario')
                                    ->label('Sucursal')
                                    ->default(fn () => Auth::user()->sucursal?->descripcion ?? 'Sin sucursal')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->helperText('Sucursal del usuario conectado'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('descripcion')
                    ->label('Artículo')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->weight('bold')
                    ->tooltip(fn ($record) => $record->descripcion),

                TextColumn::make('marcas_ar.descripcion')
                    ->label('Marca')
                    ->searchable()
                    ->sortable(),

                /*TextColumn::make('tipo_articulo_ar.descripcion')
                    ->label('Tipo')
                    ->badge()
                    ->color('info')
                    ->searchable(),*/

                TextColumn::make('tipoRepuesto.descripcion')
                    ->label('Categoría')
                    ->badge()
                    ->color(fn ($record) => $record->tipoRepuesto?->color ?? 'gray')
                    ->searchable(),

                IconColumn::make('activo')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('usuario_alta')
                    ->label('Usuario Alta')
                    ->searchable(),

                TextColumn::make('fec_alta')
                    ->label('Fecha Alta')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                //    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                ]),
            ])

          //  ->defaultSort('fec_alta', 'desc')
            ->persistSortInSession()
           ->persistSearchInSession()
           ->persistFiltersInSession();
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
            'index' => Pages\ListArticulos::route('/'),
            'create' => Pages\CreateArticulo::route('/create'),
            'view' => Pages\ViewArticulo::route('/{record}'),
            'edit' => Pages\EditArticulo::route('/{record}/edit'),
        ];
    }
}
