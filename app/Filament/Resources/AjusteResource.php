<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AjusteResource\Pages;
use App\Models\AjusteCabecera;
use App\Models\AjusteDetalle;
use App\Models\TipoAjuste;
use App\Models\Articulos;
use App\Models\ExistenciaArticulo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;

class AjusteResource extends Resource
{
    protected static ?string $model = AjusteCabecera::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
     protected static ?string $navigationGroup = 'Gestión de Compra';
    protected static ?string $navigationLabel = 'Ajuste de Stock';
    protected static ?string $modelLabel = 'Ajuste';
    protected static ?string $pluralModelLabel = 'Ajustes de Stock';
    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del Ajuste')
                ->schema([
                    Forms\Components\Grid::make(4)->schema([
                        Forms\Components\Select::make('cod_sucursal')
                            ->label('Sucursal')
                            ->relationship('sucursal', 'descripcion')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->default(1)
                            ->disabled(fn (?AjusteCabecera $record) => $record && !$record->es_editable),

                        Forms\Components\Select::make('tipo_ajuste')
                            ->label('Tipo de Ajuste')
                            ->relationship('tipoAjuste', 'descripcion')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->descripcion . ' (' . ($record->tipo === 'E' ? 'Entrada' : 'Salida') . ')')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2)
                            ->live()
                            ->disabled(fn (?AjusteCabecera $record) => $record && !$record->es_editable),

                        Forms\Components\DatePicker::make('fec_ajuste')
                            ->label('Fecha')
                            ->default(now())
                            ->required()
                            ->disabled(fn (?AjusteCabecera $record) => $record && !$record->es_editable),

                        // Campos ocultos para valores fijos
                        Forms\Components\Hidden::make('tipo')->default('AJS'),
                        Forms\Components\Hidden::make('serie')->default('A'),
                        Forms\Components\Hidden::make('estado')->default('P'),
                        Forms\Components\Hidden::make('cod_sucursal'),

                        Forms\Components\TextInput::make('usuario_alta')
                            ->label('Usuario Alta')
                            ->default(fn () => auth()->user()->name)
                            ->disabled()
                            ->dehydrated(),

                        Forms\Components\TextInput::make('fec_alta')
                            ->label('Fecha Alta')
                            ->default(now())
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y') : now()->format('d/m/Y')),

                        Forms\Components\Textarea::make('observacion')
                            ->label('Observación')
                            ->rows(2)
                            ->columnSpan(4)
                            ->disabled(fn (?AjusteCabecera $record) => $record && !$record->es_editable),
                    ])
                ]),

            Forms\Components\Section::make('Detalle del Ajuste')
                ->schema([
                    Forms\Components\Repeater::make('detalles')
                        ->relationship('detalles')
                        ->label('Artículos')
                        ->schema([
                            Forms\Components\Select::make('cod_articulo')
                                ->label('Artículo')
                                ->relationship('articulo', 'descripcion')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(6)
                                ->live()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    if ($state) {
                                        $codSucursal = $get('../../cod_sucursal') ?? 1;
                                        $existencia = ExistenciaArticulo::where('cod_articulo', $state)
                                            ->where('cod_sucursal', $codSucursal)
                                            ->first();
                                        $stock = $existencia ? $existencia->stock_actual : 0;
                                        $set('stock_display', number_format($stock, 0, ',', '.'));
                                    } else {
                                        $set('stock_display', '-');
                                    }
                                }),

                            Forms\Components\TextInput::make('stock_display')
                                ->label('Stock Actual')
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(2),

                            Forms\Components\TextInput::make('cantidad')
                                ->label('Cantidad')
                                ->numeric()
                                ->required()
                                ->minValue(1)
                                ->columnSpan(4),
                        ])
                        ->columns(12)
                        ->default([])
                        ->disabled(fn (?AjusteCabecera $record) => $record && !$record->es_editable)
                        ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                            $data['tipo'] = 'AJS';
                            $data['serie'] = 'A';
                            return $data;
                        })
                        ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                            $data['tipo'] = 'AJS';
                            $data['serie'] = 'A';
                            return $data;
                        }),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nro_ajuste')
                    ->label('Nro.')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipoAjuste.descripcion')
                    ->label('Tipo de Ajuste')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipoAjuste.tipo')
                    ->label('Movimiento')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'E' ? 'Entrada' : 'Salida')
                    ->colors([
                        'success' => 'E',
                        'danger' => 'S',
                    ]),
                Tables\Columns\TextColumn::make('fec_ajuste')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'P' => 'Pendiente',
                        'C' => 'Confirmado',
                        'A' => 'Anulado',
                        default => $state,
                    })
                    ->colors([
                        'warning' => 'P',
                        'success' => 'C',
                        'danger' => 'A',
                    ]),
                Tables\Columns\TextColumn::make('usuario_alta')
                    ->label('Usuario'),
                Tables\Columns\TextColumn::make('fec_alta')
                    ->label('Fecha Alta')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'P' => 'Pendiente',
                        'C' => 'Confirmado',
                        'A' => 'Anulado',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (AjusteCabecera $record) => $record->es_editable && auth()->user()->can('update_ajuste::cabecera')),
                Tables\Actions\Action::make('confirmar')
                    ->label('Confirmar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Ajuste')
                    ->modalDescription('¿Está seguro de confirmar este ajuste? Se realizará el movimiento de stock correspondiente.')
                    ->modalSubmitActionLabel('Confirmar')
                    ->visible(fn (AjusteCabecera $record) => true)
                    ->action(function (AjusteCabecera $record) {
                        $record->update(['estado' => 'C']);
                        Notification::make()
                            ->title('Ajuste confirmado')
                            ->success()
                            ->body('El ajuste ha sido confirmado y el stock actualizado.')
                            ->send();
                    }),
                Tables\Actions\Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Anular Ajuste')
                    ->modalDescription('¿Está seguro de anular este ajuste? Se revertirá el movimiento de stock.')
                    ->modalSubmitActionLabel('Anular')
                    ->visible(fn (AjusteCabecera $record) => true)
                    ->action(function (AjusteCabecera $record) {
                        $record->update(['estado' => 'A']);
                        Notification::make()
                            ->title('Ajuste anulado')
                            ->warning()
                            ->body('El ajuste ha sido anulado y el stock revertido.')
                            ->send();
                    }),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('nro_ajuste', 'desc');
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
            'index' => Pages\ListAjustes::route('/'),
            'create' => Pages\CreateAjuste::route('/create'),
            'edit' => Pages\EditAjuste::route('/{record}/edit'),
        ];
    }
}
