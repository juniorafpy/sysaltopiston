<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AperturaCajaResource\Pages;
use App\Models\AperturaCaja;
use App\Models\Caja;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class AperturaCajaResource extends Resource
{
    protected static ?string $model = AperturaCaja::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Apertura de Caja';
    protected static ?string $modelLabel = 'Apertura de Caja';
    protected static ?string $pluralModelLabel = 'Aperturas de Caja';
    protected static ?string $navigationGroup = 'Ventas';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información de Apertura')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('cod_caja')
                            ->label('Caja')
                            ->options(function () {
                                $codSucursal = Auth::user()->cod_sucursal ?? 1;
                                return Caja::activas()
                                    ->where('cod_sucursal', $codSucursal)
                                    ->get()
                                    ->pluck('descripcion', 'cod_caja');
                            })
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn (?AperturaCaja $record) => $record !== null)
                            ->helperText('Cajas activas de tu sucursal'),

                        Forms\Components\TextInput::make('usuario')
                            ->label('Usuario')
                            ->default(fn () => Auth::user()->name)
                            ->disabled()
                            ->dehydrated()
                            ->required(),

                        Forms\Components\Hidden::make('cod_sucursal')
                            ->default(Auth::user()->cod_sucursal ?? null),

                        Forms\Components\DatePicker::make('fecha_apertura')
                            ->label('Fecha de Apertura')
                            ->default(now())
                            ->required()
                            ->disabled(fn (?AperturaCaja $record) => $record !== null)
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TimePicker::make('hora_apertura')
                            ->label('Hora de Apertura')
                            ->default(now())
                            ->required()
                            ->disabled(fn (?AperturaCaja $record) => $record !== null)
                            ->seconds(false),

                        Forms\Components\TextInput::make('monto_inicial')
                            ->label('Monto Inicial')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->suffix('Gs.')
                            ->disabled(fn (?AperturaCaja $record) => $record !== null),

                        Forms\Components\Textarea::make('observaciones_apertura')
                            ->label('Observaciones')
                            ->rows(3)
                            ->columnSpanFull()
                            ->disabled(fn (?AperturaCaja $record) => $record !== null),
                    ]),
                ])
                ->collapsible()
                ->collapsed(fn (?AperturaCaja $record) => $record !== null),

            Forms\Components\Section::make('Resumen de Caja')
                ->schema([
                    Forms\Components\Grid::make(4)->schema([
                        Forms\Components\TextInput::make('monto_inicial')
                            ->label('Monto Inicial')
                            ->disabled()
                            ->dehydrated()
                            ->suffix('Gs.'),

                        Forms\Components\TextInput::make('total_ingresos')
                            ->label('Total Ingresos')
                            ->default(fn (AperturaCaja $record) => $record->total_ingresos)
                            ->disabled()
                            ->suffix('Gs.'),

                        Forms\Components\TextInput::make('total_egresos')
                            ->label('Total Egresos')
                            ->default(fn (AperturaCaja $record) => $record->total_egresos)
                            ->disabled()
                            ->suffix('Gs.'),

                        Forms\Components\TextInput::make('saldo_esperado')
                            ->label('Saldo Esperado (Sistema)')
                            ->default(fn (AperturaCaja $record) => $record->saldo_esperado_calculado)
                            ->disabled()
                            ->suffix('Gs.')
                            ->extraAttributes(['style' => 'font-weight: bold; color: #2563eb;']),
                    ]),
                ])
                ->visible(fn (?AperturaCaja $record) => $record?->estado === 'Abierta'),

            Forms\Components\Section::make('Arqueo de Caja - Conteo Físico')
                ->description('Ingrese los montos contados físicamente')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('efectivo_real')
                            ->label('Efectivo')
                            ->numeric()
                            ->default(0)
                            ->suffix('Gs.')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, $get) {
                                $total = (float)$state + (float)($get('tarjetas_real') ?? 0) + (float)($get('transferencias_real') ?? 0) + (float)($get('cheques_real') ?? 0);
                                $set('total_fisico', $total);
                                $set('diferencia', $total - (float)($get('saldo_esperado') ?? 0));
                            }),

                        Forms\Components\TextInput::make('tarjetas_real')
                            ->label('Tarjetas')
                            ->numeric()
                            ->default(0)
                            ->suffix('Gs.')
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, $get) {
                                $total = (float)($get('efectivo_real') ?? 0) + (float)$state + (float)($get('transferencias_real') ?? 0) + (float)($get('cheques_real') ?? 0);
                                $set('total_fisico', $total);
                                $set('diferencia', $total - (float)($get('saldo_esperado') ?? 0));
                            }),

                        Forms\Components\TextInput::make('transferencias_real')
                            ->label('Transferencias')
                            ->numeric()
                            ->default(0)
                            ->suffix('Gs.')
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, $get) {
                                $total = (float)($get('efectivo_real') ?? 0) + (float)($get('tarjetas_real') ?? 0) + (float)$state + (float)($get('cheques_real') ?? 0);
                                $set('total_fisico', $total);
                                $set('diferencia', $total - (float)($get('saldo_esperado') ?? 0));
                            }),

                        Forms\Components\TextInput::make('cheques_real')
                            ->label('Cheques')
                            ->numeric()
                            ->default(0)
                            ->suffix('Gs.')
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, $get) {
                                $total = (float)($get('efectivo_real') ?? 0) + (float)($get('tarjetas_real') ?? 0) + (float)($get('transferencias_real') ?? 0) + (float)$state;
                                $set('total_fisico', $total);
                                $set('diferencia', $total - (float)($get('saldo_esperado') ?? 0));
                            }),

                        Forms\Components\TextInput::make('total_fisico')
                            ->label('Total Físico')
                            ->numeric()
                            ->default(0)
                            ->suffix('Gs.')
                            ->disabled()
                            ->dehydrated()
                            ->extraAttributes(['style' => 'font-weight: bold; color: #059669;']),

                        Forms\Components\TextInput::make('diferencia')
                            ->label('Diferencia')
                            ->numeric()
                            ->default(0)
                            ->suffix('Gs.')
                            ->disabled()
                            ->extraInputAttributes(fn ($state) => [
                                'style' => $state > 0 
                                    ? 'color: #059669; font-weight: bold;' 
                                    : ($state < 0 ? 'color: #dc2626; font-weight: bold;' : 'color: #6b7280;'),
                            ]),
                    ]),

                    Forms\Components\Textarea::make('observaciones_cierre')
                        ->label('Observaciones del Cierre')
                        ->rows(2)
                        ->columnSpanFull(),
                ])
                ->visible(fn (?AperturaCaja $record) => $record?->estado === 'Abierta'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_apertura')
                    ->label('Nº')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('caja.descripcion')
                    ->label('Caja')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('usuario')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('fecha_apertura')
                    ->label('Fecha Apertura')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('monto_inicial')
                    ->label('Monto Inicial')
                    ->money('PYG', divideBy: 1)
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_cierre')
                    ->label('Fecha Cierre')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Pendiente'),
                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'success' => 'Cerrada',
                        'warning' => 'Abierta',
                    ]),
                Tables\Columns\TextColumn::make('saldo_esperado')
                    ->label('Saldo Esperado')
                    ->money('PYG', divideBy: 1)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Abierta' => 'Abierta',
                        'Cerrada' => 'Cerrada',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->label('Cerrar')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (AperturaCaja $record) => $record->estado === 'Abierta'),
            ])
            ->defaultSort('fecha_apertura', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAperturasCaja::route('/'),
            'create' => Pages\CreateAperturaCaja::route('/create'),
            'view' => Pages\ViewAperturaCaja::route('/{record}'),
            'edit' => Pages\EditAperturaCaja::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return AperturaCaja::abiertas()->count() > 0 ? (string) AperturaCaja::abiertas()->count() : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
