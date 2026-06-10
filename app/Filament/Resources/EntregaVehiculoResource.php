<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EntregaVehiculoResource\Pages;
use App\Models\EntregaVehiculo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EntregaVehiculoResource extends Resource
{
    protected static ?string $model = EntregaVehiculo::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationGroup = 'Gestión Servicios';
    protected static ?string $modelLabel = 'Entrega de Vehículo';
    protected static ?string $pluralModelLabel = 'Entregas de Vehículos';
    protected static ?int $navigationSort = 22;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la Entrega')
                    ->schema([
                        Forms\Components\Select::make('orden_servicio_id')
                            ->label('Orden de Servicio')
                            ->relationship('ordenServicio', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "OS #{$record->id} - " . ($record->cliente?->nombre_completo ?? 'Sin cliente'))
                            ->searchable()
                            ->required()
                            ->disabled(),

                        Forms\Components\DateTimePicker::make('fecha_entrega')
                            ->label('Fecha y hora de entrega')
                            ->required(),

                        Forms\Components\TextInput::make('persona_recibe')
                            ->label('Persona que recibe')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('documento_recibe')
                            ->label('Documento (CI/RUC)')
                            ->maxLength(50),

                        Forms\Components\TextInput::make('kilometraje_salida')
                            ->label('Kilometraje de salida')
                            ->numeric()
                            ->required(),

                        Forms\Components\Toggle::make('recibe_titular')
                            ->label('El cliente titular retiró el vehículo')
                            ->default(false),

                        Forms\Components\Textarea::make('observaciones')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('ordenServicio.id')
                    ->label('OS #')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('ordenServicio.cliente.persona.nombres')
                    ->label('Cliente')
                    ->getStateUsing(function ($record) {
                        return $record->ordenServicio?->cliente?->nombre_completo ?? 'N/A';
                    })
                    ->searchable(query: function ($query, $search) {
                        return $query->whereHas('ordenServicio.cliente.persona', function ($q) use ($search) {
                            $q->where('nombres', 'ilike', "%{$search}%")
                              ->orWhere('apellidos', 'ilike', "%{$search}%")
                              ->orWhere('razon_social', 'ilike', "%{$search}%");
                        });
                    }),

                Tables\Columns\TextColumn::make('fecha_entrega')
                    ->label('Fecha de Entrega')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('persona_recibe')
                    ->label('Recibió')
                    ->searchable(),

                Tables\Columns\TextColumn::make('documento_recibe')
                    ->label('Documento'),

                Tables\Columns\TextColumn::make('kilometraje_salida')
                    ->label('Km Salida')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\IconColumn::make('recibe_titular')
                    ->label('Titular')
                    ->boolean(),

                Tables\Columns\TextColumn::make('usuario_alta')
                    ->label('Registró'),

                Tables\Columns\TextColumn::make('fec_alta')
                    ->label('Fec. Registro')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('recibe_titular')
                    ->label('Recibió titular')
                    ->options([
                        true => 'Sí',
                        false => 'No',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('imprimir')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn ($record) => route('entrega-vehiculo.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->defaultSort('fecha_entrega', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEntregaVehiculos::route('/'),
            'view' => Pages\ViewEntregaVehiculo::route('/{record}'),
            'edit' => Pages\EditEntregaVehiculo::route('/{record}/edit'),
        ];
    }
}
