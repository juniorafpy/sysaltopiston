<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromocionResource\Pages;
use App\Models\Promocion;
use App\Models\Articulos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class PromocionResource extends Resource
{
    protected static ?string $model = Promocion::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Promociones';

    protected static ?string $modelLabel = 'Promoción';

    protected static ?string $pluralModelLabel = 'Promociones';

    protected static ?string $navigationGroup = 'Ventas';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la Promoción')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('nombre')
                                    ->label('Nombre de la Promoción')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),

                                Forms\Components\Toggle::make('activo')
                                    ->label('Activa')
                                    ->default(true)
                                    ->inline(false),
                            ]),

                        Forms\Components\Textarea::make('descripcion')
                            ->label('Descripción')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('fecha_inicio')
                                    ->label('Fecha Inicio')
                                    ->required()
                                    ->default(now())
                                    ->native(false),

                                Forms\Components\DatePicker::make('fecha_fin')
                                    ->label('Fecha Fin')
                                    ->required()
                                    ->native(false)
                                    ->afterOrEqual('fecha_inicio'),
                            ]),
                    ]),

                Forms\Components\Section::make('Artículos en Promoción')
                    ->schema([
                        Forms\Components\Repeater::make('detalles')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('articulo_id')
                                    ->label('Artículo')
                                    ->relationship('articulo', 'descripcion')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->distinct()
                                    ->columnSpan(2),

                                Forms\Components\TextInput::make('porcentaje_descuento')
                                    ->label('% Descuento')
                                    ->numeric()
                                    ->required()
                                    ->suffix('%')
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->step(0.01)
                                    ->columnSpan(1),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Agregar Artículo')
                            ->reorderableWithButtons()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                                $state['articulo_id']
                                    ? Articulos::find($state['articulo_id'])?->descripcion . ' - ' . ($state['porcentaje_descuento'] ?? '0') . '%'
                                    : null
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Promoción')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->label('Desde')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_fin')
                    ->label('Hasta')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\IconColumn::make('activo')
                    ->label('Activa')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('vigencia')
                    ->label('Estado')
                    ->badge()
                    ->getStateUsing(function (Promocion $record): string {
                        if (!$record->activo) {
                            return 'Inactiva';
                        }

                        $hoy = Carbon::today();

                        if ($hoy < $record->fecha_inicio) {
                            return 'Programada';
                        } elseif ($hoy > $record->fecha_fin) {
                            return 'Vencida';
                        } else {
                            return 'Vigente';
                        }
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Vigente' => 'success',
                        'Programada' => 'info',
                        'Vencida' => 'danger',
                        'Inactiva' => 'gray',
                    }),

                Tables\Columns\TextColumn::make('detalles_count')
                    ->label('Artículos')
                    ->counts('detalles')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('vigentes')
                    ->label('Solo Vigentes')
                    ->query(fn (Builder $query): Builder => $query->vigentes()),

                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Activa'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListPromocions::route('/'),
            'create' => Pages\CreatePromocion::route('/create'),
            'edit' => Pages\EditPromocion::route('/{record}/edit'),
        ];
    }
}
