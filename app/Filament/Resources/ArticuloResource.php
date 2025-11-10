<?php

namespace App\Filament\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Articulo;
use Filament\Forms\Form;
use App\Models\Articulos;
use Filament\Tables\Table;
use Tables\Columns\BadgeColumn;
use Filament\Resources\Resource;
use Filament\Forms\Components\Card;
use Filament\Forms\Components\Grid;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\BooleanColumn;
use App\Filament\Resources\ArticuloResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ArticuloResource\RelationManagers;

class ArticuloResource extends Resource
{
    protected static ?string $model = Articulos::class;

    protected static ?string $navigationGroup = 'Definiciones';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $modelLabel = 'Artículo';

    protected static ?string $pluralModelLabel = 'Artículos';

    protected static ?string $recordTitleAttribute = 'descripcion';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Artículo')
                    ->description('Complete los datos básicos del artículo')
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('descripcion')
                                    ->label('Descripción')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Filtro de aceite para motor')
                                    ->columnSpan(2),

                                Forms\Components\Select::make('cod_marca')
                                    ->label('Marca')
                                    ->options(fn () => \App\Models\Marcas::pluck('descripcion', 'cod_marca'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('descripcion')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $marca = \App\Models\Marcas::create($data);
                                        return $marca->cod_marca;
                                    }),

                                Forms\Components\Select::make('cod_modelo')
                                    ->label('Modelo')
                                    ->options(fn () => \App\Models\Modelos::pluck('descripcion', 'cod_modelo'))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('descripcion')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        $modelo = \App\Models\Modelos::create($data);
                                        return $modelo->cod_modelo;
                                    }),

                                Forms\Components\Select::make('cod_tip_articulo')
                                    ->label('Tipo de Artículo')
                                    ->options(fn () => \App\Models\TipoArticulos::pluck('descripcion', 'cod_tip_articulo'))
                                    ->searchable()
                                    ->preload()
                                    ->required(),

                                Forms\Components\Select::make('cod_medida')
                                    ->label('Unidad de Medida')
                                    ->options(fn () => \App\Models\Medidas::pluck('descripcion', 'cod_medida'))
                                    ->searchable()
                                    ->preload()
                                    ->placeholder('Opcional'),
                            ]),
                    ]),

                Forms\Components\Section::make('Precios y Costos')
                    ->description('Configure los precios del artículo')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('costo')
                                    ->label('Costo')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Gs.')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(1000)
                                    ->helperText('Precio de compra del artículo'),

                                Forms\Components\TextInput::make('precio')
                                    ->label('Precio de Venta')
                                    ->numeric()
                                    ->required()
                                    ->prefix('Gs.')
                                    ->default(0)
                                    ->minValue(0)
                                    ->step(1000)
                                    ->helperText('Precio de venta al público'),

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
                    ]),

                Forms\Components\Section::make('Imagen del Artículo')
                    ->description('Suba una imagen del artículo (opcional)')
                    ->icon('heroicon-o-photo')
                    ->collapsed()
                    ->schema([
                        Forms\Components\FileUpload::make('image')
                            ->label('Imagen')
                            ->image()
                            ->disk('public')
                            ->directory('articulos')
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                '1:1',
                                '4:3',
                            ])
                            ->maxSize(5120) // 5MB
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Formato: JPG, PNG o WebP. Tamaño máximo: 5MB'),
                    ]),

                Forms\Components\Section::make('Estado y Auditoría')
                    ->description('Estado e información de registro')
                    ->icon('heroicon-o-information-circle')
                    ->collapsed()
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Toggle::make('activo')
                                    ->label('Activo')
                                    ->default(true)
                                    ->inline(false)
                                    ->helperText('Artículo disponible para uso'),

                                Forms\Components\TextInput::make('usuario_alta')
                                    ->label('Usuario Alta')
                                    ->default(fn () => Auth::user()->name)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visible(fn ($record) => $record !== null),

                                Forms\Components\TextInput::make('fec_alta')
                                    ->label('Fecha Alta')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->visible(fn ($record) => $record !== null),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Imagen')
                    ->circular()
                    ->defaultImageUrl(url('/images/no-image.png'))
                    ->size(50),

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Artículo')
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->descripcion),

                Tables\Columns\TextColumn::make('marcas_ar.descripcion')
                    ->label('Marca')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('modelos_ar.descripcion')
                    ->label('Modelo')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('tipo_articulo_ar.descripcion')
                    ->label('Tipo')
                    ->badge()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('costo')
                    ->label('Costo')
                    ->money('PYG')
                    ->sortable()
                    ->toggleable()
                    ->summarize([
                        Tables\Columns\Summarizers\Average::make()
                            ->money('PYG')
                            ->label('Promedio'),
                    ]),

                Tables\Columns\TextColumn::make('precio')
                    ->label('Precio')
                    ->money('PYG')
                    ->sortable()
                    ->weight('bold')
                    ->summarize([
                        Tables\Columns\Summarizers\Average::make()
                            ->money('PYG')
                            ->label('Promedio'),
                    ]),

                Tables\Columns\TextColumn::make('margen')
                    ->label('Margen %')
                    ->getStateUsing(function ($record) {
                        if ($record->costo > 0 && $record->precio > 0) {
                            $margen = (($record->precio - $record->costo) / $record->costo) * 100;
                            return number_format($margen, 1) . '%';
                        }
                        return 'N/A';
                    })
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === 'N/A' => 'gray',
                        floatval($state) < 20 => 'danger',
                        floatval($state) < 40 => 'warning',
                        default => 'success',
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("((precio - costo) / costo) $direction");
                    })
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('activo')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => $state == 1 ? 'Activo' : 'Inactivo')
                    ->colors([
                        'success' => 1,
                        'danger' => 0,
                    ])
                    ->icon(fn ($state) => $state == 1 ? 'heroicon-s-check-circle' : 'heroicon-s-x-circle'),

                Tables\Columns\TextColumn::make('medida_ar.descripcion')
                    ->label('Medida')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('usuario_alta')
                    ->label('Usuario Alta')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),

                Tables\Columns\TextColumn::make('fec_alta')
                    ->label('Fecha Alta')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('activo')
                    ->label('Estado')
                    ->options([
                        1 => 'Activo',
                        0 => 'Inactivo',
                    ])
                    ->default(1),

                Tables\Filters\SelectFilter::make('cod_marca')
                    ->label('Marca')
                    ->relationship('marcas_ar', 'descripcion')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('cod_tip_articulo')
                    ->label('Tipo de Artículo')
                    ->relationship('tipo_articulo_ar', 'descripcion')
                    ->searchable()
                    ->preload(),

                Tables\Filters\Filter::make('precio')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('precio_desde')
                                    ->label('Precio desde')
                                    ->numeric()
                                    ->prefix('Gs.'),
                                Forms\Components\TextInput::make('precio_hasta')
                                    ->label('Precio hasta')
                                    ->numeric()
                                    ->prefix('Gs.'),
                            ]),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['precio_desde'], fn ($query, $precio) => $query->where('precio', '>=', $precio))
                            ->when($data['precio_hasta'], fn ($query, $precio) => $query->where('precio', '<=', $precio));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['precio_desde'] ?? null) {
                            $indicators[] = 'Precio desde: Gs. ' . number_format($data['precio_desde']);
                        }
                        if ($data['precio_hasta'] ?? null) {
                            $indicators[] = 'Precio hasta: Gs. ' . number_format($data['precio_hasta']);
                        }
                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activar')
                        ->label('Activar seleccionados')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['activo' => true]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('desactivar')
                        ->label('Desactivar seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn ($records) => $records->each->update(['activo' => false]))
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('fec_alta', 'desc')
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
