<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AperturaCajaResource\Pages;
use App\Models\AperturaCaja;
use App\Models\ArqueoCaja;
use App\Models\Caja;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
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
    protected static ?string $navigationGroup = 'Gestión Ventas';
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
                            ->disabled()
                            ->dehydrated()
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TimePicker::make('hora_apertura')
                            ->label('Hora de Apertura')
                            ->default(now())
                            ->required()
                            ->disabled()
                            ->dehydrated()
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
                            ->suffix('Gs.')
                            ->mask(RawJs::make('$money($input, \'.\', \',\', 0)'))
                            ->stripCharacters('.'),

                        Forms\Components\TextInput::make('total_ingresos')
                            ->label('Total Ingresos')
                            ->default(fn (?AperturaCaja $record) => $record?->total_ingresos ?? 0)
                            ->disabled()
                            ->suffix('Gs.'),

                        Forms\Components\TextInput::make('total_egresos')
                            ->label('Total Egresos')
                            ->default(fn (?AperturaCaja $record) => $record?->total_egresos ?? 0)
                            ->disabled()
                            ->suffix('Gs.'),

                        Forms\Components\TextInput::make('saldo_esperado')
                            ->label('Saldo Esperado (Sistema)')
                            ->default(fn (?AperturaCaja $record) => $record?->saldo_esperado_calculado ?? 0)
                            ->disabled()
                            ->suffix('Gs.')
                            ->extraAttributes(['style' => 'font-weight: bold; color: #2563eb;']),
                    ]),
                ])
                ->visible(fn (?AperturaCaja $record) => $record?->estado === 'Abierta'),

            Forms\Components\Section::make('Totales del Sistema')
                ->description('Montos calculados automáticamente desde los cobros registrados')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\Placeholder::make('efectivo_sistema')
                            ->label('Efectivo')
                            ->content(fn (?AperturaCaja $record) => 'Gs. ' . number_format($record?->cobros()
                                ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                                ->where('cobros_formas_pago.cod_forma_cobro', 1)
                                ->sum('cobros_formas_pago.monto') ?? 0, 0, ',', '.')),

                        Forms\Components\Placeholder::make('tarjetas_sistema')
                            ->label('Tarjetas')
                            ->content(fn (?AperturaCaja $record) => 'Gs. ' . number_format($record?->cobros()
                                ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                                ->whereIn('cobros_formas_pago.cod_forma_cobro', [2, 3])
                                ->sum('cobros_formas_pago.monto') ?? 0, 0, ',', '.')),

                        Forms\Components\Placeholder::make('transferencias_sistema')
                            ->label('Transferencias')
                            ->content(fn (?AperturaCaja $record) => 'Gs. ' . number_format($record?->cobros()
                                ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                                ->where('cobros_formas_pago.cod_forma_cobro', 4)
                                ->sum('cobros_formas_pago.monto') ?? 0, 0, ',', '.')),

                        Forms\Components\Placeholder::make('cheques_sistema')
                            ->label('Cheques')
                            ->content(fn (?AperturaCaja $record) => 'Gs. ' . number_format($record?->cobros()
                                ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                                ->where('cobros_formas_pago.cod_forma_cobro', 5)
                                ->sum('cobros_formas_pago.monto') ?? 0, 0, ',', '.')),

                        Forms\Components\Placeholder::make('total_sistema')
                            ->label('Total Sistema')
                            ->content(fn (?AperturaCaja $record) => new \Illuminate\Support\HtmlString(
                                '<span style="font-weight: bold; color: #2563eb;">Gs. ' . number_format($record?->total_ingresos ?? 0, 0, ',', '.') . '</span>'
                            )),
                    ]),
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
            
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Abierta' => 'Abierta',
                        'Cerrada' => 'Cerrada',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('cerrar')
                    ->label('Cerrar')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (AperturaCaja $record): bool => $record->estado === 'Abierta')
                    ->requiresConfirmation()
                    ->modalHeading('Cerrar Caja')
                    ->modalDescription('Confirme el cierre de caja. Se registrará el arqueo con los datos actuales.')
                    ->modalSubmitActionLabel('Cerrar Caja')
                    ->action(function (AperturaCaja $record) {
                        $efectivo = $record->cobros()
                            ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                            ->where('cobros_formas_pago.cod_forma_cobro', 1)
                            ->sum('cobros_formas_pago.monto') ?? 0;
                        $tarjetas = $record->cobros()
                            ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                            ->whereIn('cobros_formas_pago.cod_forma_cobro', [2, 3])
                            ->sum('cobros_formas_pago.monto') ?? 0;
                        $transferencias = $record->cobros()
                            ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                            ->where('cobros_formas_pago.cod_forma_cobro', 4)
                            ->sum('cobros_formas_pago.monto') ?? 0;
                        $cheques = $record->cobros()
                            ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                            ->where('cobros_formas_pago.cod_forma_cobro', 5)
                            ->sum('cobros_formas_pago.monto') ?? 0;
                        $totalSistema = $efectivo + $tarjetas + $transferencias + $cheques;

                        $record->update([
                            'estado' => 'Cerrada',
                            'fecha_cierre' => now()->toDateString(),
                            'hora_cierre' => now()->toTimeString(),
                            'saldo_esperado' => $totalSistema,
                            'diferencia' => 0,
                        ]);

                        ArqueoCaja::create([
                            'cod_apertura' => $record->cod_apertura,
                            'efectivo_sistema' => $efectivo,
                            'tarjetas_sistema' => $tarjetas,
                            'transferencias_sistema' => $transferencias,
                            'cheques_sistema' => $cheques,
                            'total_sistema' => $totalSistema,
                            'efectivo_fisico' => 0,
                            'tarjetas_fisico' => 0,
                            'transferencias_fisico' => 0,
                            'cheques_fisico' => 0,
                            'total_fisico' => 0,
                            'diferencia' => -$totalSistema,
                            'observaciones' => 'Cierre sin arqueo previo',
                            'usuario_alta' => Auth::user()->name,
                            'fecha_alta' => now(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Caja cerrada')
                            ->body('La caja ha sido cerrada exitosamente.')
                            ->send();
                    }),

                Tables\Actions\Action::make('arqueo')
                    ->label('Arqueo')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->visible(fn (AperturaCaja $record): bool => $record->estado === 'Abierta')
                    ->modalHeading('Arqueo de Caja')
                    ->modalDescription('Ingrese los montos contados físicamente antes de cerrar.')
                    ->modalSubmitActionLabel('Guardar y Cerrar')
                    ->form(function (AperturaCaja $record) {
                        $efectivo = $record->cobros()
                            ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                            ->where('cobros_formas_pago.cod_forma_cobro', 1)
                            ->sum('cobros_formas_pago.monto') ?? 0;
                        $tarjetas = $record->cobros()
                            ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                            ->whereIn('cobros_formas_pago.cod_forma_cobro', [2, 3])
                            ->sum('cobros_formas_pago.monto') ?? 0;
                        $transferencias = $record->cobros()
                            ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                            ->where('cobros_formas_pago.cod_forma_cobro', 4)
                            ->sum('cobros_formas_pago.monto') ?? 0;
                        $cheques = $record->cobros()
                            ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                            ->where('cobros_formas_pago.cod_forma_cobro', 5)
                            ->sum('cobros_formas_pago.monto') ?? 0;
                        $total = $efectivo + $tarjetas + $transferencias + $cheques;

                        return [
                            Forms\Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('efectivo_sistema')
                                    ->label('Efectivo Sistema')
                                    ->default($efectivo)
                                    ->disabled()
                                    ->dehydrated()
                                    ->suffix('Gs.'),

                                Forms\Components\TextInput::make('efectivo_fisico')
                                    ->label('Efectivo Físico')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs.')
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $total = (float)($get('efectivo_fisico') ?? 0) + (float)($get('tarjetas_fisico') ?? 0) + (float)($get('transferencias_fisico') ?? 0) + (float)($get('cheques_fisico') ?? 0);
                                        $set('total_fisico', $total);
                                        $set('diferencia', $total - (float)($get('total_sistema') ?? 0));
                                    })
                                    ->required(),

                                Forms\Components\TextInput::make('tarjetas_sistema')
                                    ->label('Tarjetas Sistema')
                                    ->default($tarjetas)
                                    ->disabled()
                                    ->dehydrated()
                                    ->suffix('Gs.'),

                                Forms\Components\TextInput::make('tarjetas_fisico')
                                    ->label('Tarjetas Físico')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs.')
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $total = (float)($get('efectivo_fisico') ?? 0) + (float)($get('tarjetas_fisico') ?? 0) + (float)($get('transferencias_fisico') ?? 0) + (float)($get('cheques_fisico') ?? 0);
                                        $set('total_fisico', $total);
                                        $set('diferencia', $total - (float)($get('total_sistema') ?? 0));
                                    })
                                    ->required(),

                                Forms\Components\TextInput::make('transferencias_sistema')
                                    ->label('Transferencias Sistema')
                                    ->default($transferencias)
                                    ->disabled()
                                    ->dehydrated()
                                    ->suffix('Gs.'),

                                Forms\Components\TextInput::make('transferencias_fisico')
                                    ->label('Transferencias Físico')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs.')
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $total = (float)($get('efectivo_fisico') ?? 0) + (float)($get('tarjetas_fisico') ?? 0) + (float)($get('transferencias_fisico') ?? 0) + (float)($get('cheques_fisico') ?? 0);
                                        $set('total_fisico', $total);
                                        $set('diferencia', $total - (float)($get('total_sistema') ?? 0));
                                    })
                                    ->required(),

                                Forms\Components\TextInput::make('cheques_sistema')
                                    ->label('Cheques Sistema')
                                    ->default($cheques)
                                    ->disabled()
                                    ->dehydrated()
                                    ->suffix('Gs.'),

                                Forms\Components\TextInput::make('cheques_fisico')
                                    ->label('Cheques Físico')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs.')
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $total = (float)($get('efectivo_fisico') ?? 0) + (float)($get('tarjetas_fisico') ?? 0) + (float)($get('transferencias_fisico') ?? 0) + (float)($get('cheques_fisico') ?? 0);
                                        $set('total_fisico', $total);
                                        $set('diferencia', $total - (float)($get('total_sistema') ?? 0));
                                    })
                                    ->required(),

                                Forms\Components\TextInput::make('total_sistema')
                                    ->label('Total Sistema')
                                    ->default($total)
                                    ->disabled()
                                    ->dehydrated()
                                    ->suffix('Gs.'),

                                Forms\Components\TextInput::make('total_fisico')
                                    ->label('Total Físico')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated()
                                    ->suffix('Gs.'),

                                Forms\Components\TextInput::make('diferencia')
                                    ->label('Diferencia')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated()
                                    ->suffix('Gs.'),
                            ]),

                            Forms\Components\Textarea::make('observaciones')
                                ->label('Observaciones')
                                ->rows(2)
                                ->columnSpanFull(),
                        ];
                    })
                    ->action(function (AperturaCaja $record, array $data) {
                        $totalFisico = (float)($data['efectivo_fisico'] ?? 0)
                            + (float)($data['tarjetas_fisico'] ?? 0)
                            + (float)($data['transferencias_fisico'] ?? 0)
                            + (float)($data['cheques_fisico'] ?? 0);

                        $diferencia = $totalFisico - (float)($data['total_sistema'] ?? 0);

                        $record->update([
                            'estado' => 'Cerrada',
                            'fecha_cierre' => now()->toDateString(),
                            'hora_cierre' => now()->toTimeString(),
                            'saldo_esperado' => $data['total_sistema'],
                            'diferencia' => $diferencia,
                        ]);

                        ArqueoCaja::create([
                            'cod_apertura' => $record->cod_apertura,
                            'efectivo_sistema' => $data['efectivo_sistema'] ?? 0,
                            'tarjetas_sistema' => $data['tarjetas_sistema'] ?? 0,
                            'transferencias_sistema' => $data['transferencias_sistema'] ?? 0,
                            'cheques_sistema' => $data['cheques_sistema'] ?? 0,
                            'total_sistema' => $data['total_sistema'] ?? 0,
                            'efectivo_fisico' => $data['efectivo_fisico'] ?? 0,
                            'tarjetas_fisico' => $data['tarjetas_fisico'] ?? 0,
                            'transferencias_fisico' => $data['transferencias_fisico'] ?? 0,
                            'cheques_fisico' => $data['cheques_fisico'] ?? 0,
                            'total_fisico' => $totalFisico,
                            'diferencia' => $diferencia,
                            'observaciones' => $data['observaciones'] ?? null,
                            'usuario_alta' => Auth::user()->name,
                            'fecha_alta' => now(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Caja cerrada')
                            ->body('El arqueo se registró y la caja fue cerrada exitosamente.')
                            ->send();
                    }),

                Tables\Actions\Action::make('imprimir')
                    ->label('Arqueo PDF')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->visible(fn (AperturaCaja $record): bool => $record->estado === 'Cerrada')
                    ->url(fn (AperturaCaja $record) => route('apertura-caja.pdf', $record))
                    ->openUrlInNewTab(),
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
