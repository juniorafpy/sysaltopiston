<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AperturaCajaResource\Pages;
use App\Models\AperturaCaja;
use App\Models\ArqueoCaja;
use App\Models\Caja;
use App\Models\RecaudacionDepositar;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\RawJs;
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
                            ->disabled(fn (?AperturaCaja $record) => $record !== null)
                        ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
    // Limpiamos los puntos antes de validar y guardar en la base de datos
                        ->stripCharacters('.'),

                    ]),
                ])
                ->collapsible()
                ->collapsed(fn (?AperturaCaja $record) => $record !== null),

            Forms\Components\Section::make('Resumen de Caja')
                ->schema([
                    Forms\Components\Grid::make(4)->schema([
                        Forms\Components\TextInput::make('monto_inicial')
                            ->label('Monto Inicial')
                            ->formatStateUsing(fn ($state) => 'Gs. ' . number_format((float)$state, 0, ',', '.'))
                            ->disabled()
                            ->dehydrated(false)
                            ->suffix('Gs.'),

                        Forms\Components\TextInput::make('saldo_esperado')
                            ->label('Saldo Esperado (Sistema)')
                            ->formatStateUsing(fn ($state) => 'Gs. ' . number_format((float)$state, 0, ',', '.'))
                            ->default(fn (?AperturaCaja $record) => $record?->saldo_esperado_calculado ?? 0)
                            ->disabled()
                            ->dehydrated(false)
                            ->suffix('Gs.')
                            ->extraAttributes(['style' => 'font-weight: bold; color: #2563eb;']),
                    ]),
                ])
                ->visible(fn (?AperturaCaja $record) => $record?->estado === 'Abierta'),

            Forms\Components\Section::make('Totales del Sistema')
                ->description('Montos calculados automáticamente desde los cobros registrados')
                ->schema([
                    Forms\Components\Grid::make(3)->schema([
                        Forms\Components\TextInput::make('efectivo_sistema')
                            ->label('Efectivo')
                            ->formatStateUsing(fn ($state) => 'Gs. ' . number_format((float)$state, 0, ',', '.'))
                            ->default(fn (?AperturaCaja $record) => $record?->cobros()
                                ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                                ->where('cobros_formas_pago.cod_forma_cobro', 1)
                                ->sum('cobros_formas_pago.monto') ?? 0)
                            ->disabled()
                            ->dehydrated(false)
                            ->suffix('Gs.'),

                        Forms\Components\TextInput::make('tarjetas_sistema')
                            ->label('Tarjetas')
                            ->formatStateUsing(fn ($state) => 'Gs. ' . number_format((float)$state, 0, ',', '.'))
                            ->default(fn (?AperturaCaja $record) => $record?->cobros()
                                ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                                ->whereIn('cobros_formas_pago.cod_forma_cobro', [2, 3])
                                ->sum('cobros_formas_pago.monto') ?? 0)
                            ->disabled()
                            ->dehydrated(false)
                            ->suffix('Gs.'),

                        Forms\Components\TextInput::make('transferencias_sistema')
                            ->label('Transferencias')
                            ->formatStateUsing(fn ($state) => 'Gs. ' . number_format((float)$state, 0, ',', '.'))
                            ->default(fn (?AperturaCaja $record) => $record?->cobros()
                                ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                                ->where('cobros_formas_pago.cod_forma_cobro', 4)
                                ->sum('cobros_formas_pago.monto') ?? 0)
                            ->disabled()
                            ->dehydrated(false)
                            ->suffix('Gs.'),

                        Forms\Components\TextInput::make('cheques_sistema')
                            ->label('Cheques')
                            ->formatStateUsing(fn ($state) => 'Gs. ' . number_format((float)$state, 0, ',', '.'))
                            ->default(fn (?AperturaCaja $record) => $record?->cobros()
                                ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                                ->where('cobros_formas_pago.cod_forma_cobro', 5)
                                ->sum('cobros_formas_pago.monto') ?? 0)
                            ->disabled()
                            ->dehydrated(false)
                            ->suffix('Gs.'),

                        Forms\Components\TextInput::make('total_sistema')
                            ->label('Total Sistema')
                            ->formatStateUsing(fn ($state) => 'Gs. ' . number_format((float)$state, 0, ',', '.'))
                            ->default(fn (?AperturaCaja $record) => $record?->cobros()
                                ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                                ->sum('cobros_formas_pago.monto') ?? 0)
                            ->disabled()
                            ->dehydrated(false)
                            ->suffix('Gs.')
                            ->extraAttributes(['style' => 'font-weight: bold; color: #2563eb;']),
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
                    ->formatStateUsing(fn ($state) => 'Gs. ' . number_format((float)$state, 0, ',', '.'))
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
                Tables\Actions\Action::make('arqueo')
                    ->label('Arqueo')
                    ->icon('heroicon-o-calculator')
                    ->color('warning')
                    ->visible(fn (AperturaCaja $record): bool => $record->estado === 'Abierta' && $record->arqueos()->count() === 0)
                    ->modalHeading('Arqueo de Caja')
                    ->modalDescription('Ingrese los montos contados físicamente antes de cerrar.')
                    ->modalSubmitActionLabel('Guardar Arqueo')
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
                                    ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.') . ' Gs.')
                                    ->default($efectivo)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix('Gs.'),

                                Forms\Components\TextInput::make('efectivo_fisico')
                                    ->label('Efectivo Físico')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs.')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->stripCharacters('.')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $ef = (float) str_replace('.', '', (string) ($get('efectivo_fisico') ?? 0));
                                        $tj = (float) str_replace('.', '', (string) ($get('tarjetas_fisico') ?? 0));
                                        $tr = (float) str_replace('.', '', (string) ($get('transferencias_fisico') ?? 0));
                                        $ch = (float) str_replace('.', '', (string) ($get('cheques_fisico') ?? 0));
                                        $totalFisico = $ef + $tj + $tr + $ch;
                                        $totalSistema = (float) str_replace('.', '', (string) ($get('total_sistema') ?? 0));
                                        $diff = $totalFisico - $totalSistema;
                                        $set('total_fisico', number_format($totalFisico, 0, ',', '.') . ' Gs.');
                                        $set('diferencia', number_format(abs($diff), 0, ',', '.') . ' Gs.');
                                    })
                                    ->required(),

                                Forms\Components\TextInput::make('tarjetas_sistema')
                                    ->label('Tarjetas Sistema')
                                    ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.') . ' Gs.')
                                    ->default($tarjetas)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix('Gs.'),

                                Forms\Components\TextInput::make('tarjetas_fisico')
                                    ->label('Tarjetas Físico')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs.')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->stripCharacters('.')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $ef = (float) str_replace('.', '', (string) ($get('efectivo_fisico') ?? 0));
                                        $tj = (float) str_replace('.', '', (string) ($get('tarjetas_fisico') ?? 0));
                                        $tr = (float) str_replace('.', '', (string) ($get('transferencias_fisico') ?? 0));
                                        $ch = (float) str_replace('.', '', (string) ($get('cheques_fisico') ?? 0));
                                        $totalFisico = $ef + $tj + $tr + $ch;
                                        $totalSistema = (float) str_replace('.', '', (string) ($get('total_sistema') ?? 0));
                                        $diff = $totalFisico - $totalSistema;
                                        $set('total_fisico', number_format($totalFisico, 0, ',', '.') . ' Gs.');
                                        $set('diferencia', number_format(abs($diff), 0, ',', '.') . ' Gs.');
                                    })
                                    ->required(),

                                Forms\Components\TextInput::make('transferencias_sistema')
                                    ->label('Transferencias Sistema')
                                    ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.') . ' Gs.')
                                    ->default($transferencias)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix('Gs.'),

                                Forms\Components\TextInput::make('transferencias_fisico')
                                    ->label('Transferencias Físico')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs.')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->stripCharacters('.')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $ef = (float) str_replace('.', '', (string) ($get('efectivo_fisico') ?? 0));
                                        $tj = (float) str_replace('.', '', (string) ($get('tarjetas_fisico') ?? 0));
                                        $tr = (float) str_replace('.', '', (string) ($get('transferencias_fisico') ?? 0));
                                        $ch = (float) str_replace('.', '', (string) ($get('cheques_fisico') ?? 0));
                                        $totalFisico = $ef + $tj + $tr + $ch;
                                        $totalSistema = (float) str_replace('.', '', (string) ($get('total_sistema') ?? 0));
                                        $diff = $totalFisico - $totalSistema;
                                        $set('total_fisico', number_format($totalFisico, 0, ',', '.') . ' Gs.');
                                        $set('diferencia', number_format(abs($diff), 0, ',', '.') . ' Gs.');
                                    })
                                    ->required(),

                                Forms\Components\TextInput::make('cheques_sistema')
                                    ->label('Cheques Sistema')
                                    ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.') . ' Gs.')
                                    ->default($cheques)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix('Gs.'),

                                Forms\Components\TextInput::make('cheques_fisico')
                                    ->label('Cheques Físico')
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs.')
                                    ->mask(RawJs::make('$money($input, \',\', \'.\', 0)'))
                                    ->stripCharacters('.')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $ef = (float) str_replace('.', '', (string) ($get('efectivo_fisico') ?? 0));
                                        $tj = (float) str_replace('.', '', (string) ($get('tarjetas_fisico') ?? 0));
                                        $tr = (float) str_replace('.', '', (string) ($get('transferencias_fisico') ?? 0));
                                        $ch = (float) str_replace('.', '', (string) ($get('cheques_fisico') ?? 0));
                                        $totalFisico = $ef + $tj + $tr + $ch;
                                        $totalSistema = (float) str_replace('.', '', (string) ($get('total_sistema') ?? 0));
                                        $diff = $totalFisico - $totalSistema;
                                        $set('total_fisico', number_format($totalFisico, 0, ',', '.') . ' Gs.');
                                        $set('diferencia', number_format(abs($diff), 0, ',', '.') . ' Gs.');
                                    })
                                    ->required(),

                                Forms\Components\TextInput::make('total_sistema')
                                    ->label('Total Sistema')
                                    ->formatStateUsing(fn ($state) => number_format((float)$state, 0, ',', '.') . ' Gs.')
                                    ->default($total)
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix('Gs.'),

                                Forms\Components\TextInput::make('total_fisico')
                                    ->label('Total Físico')
                                    ->default('0 Gs.')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix('Gs.'),

                                Forms\Components\TextInput::make('diferencia')
                                    ->label('Diferencia')
                                    ->default('0 Gs.')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->suffix('Gs.'),
                            ]),


                        ];
                    })
                    ->action(function (AperturaCaja $record, array $data) {
                        $efFisico = (float) str_replace('.', '', (string) ($data['efectivo_fisico'] ?? 0));
                        $tjFisico = (float) str_replace('.', '', (string) ($data['tarjetas_fisico'] ?? 0));
                        $trFisico = (float) str_replace('.', '', (string) ($data['transferencias_fisico'] ?? 0));
                        $chFisico = (float) str_replace('.', '', (string) ($data['cheques_fisico'] ?? 0));
                        $totalFisico = $efFisico + $tjFisico + $trFisico + $chFisico;

                        $efSistema = $record->cobros()
                            ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                            ->where('cobros_formas_pago.cod_forma_cobro', 1)
                            ->sum('cobros_formas_pago.monto') ?? 0;
                        $tjSistema = $record->cobros()
                            ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                            ->whereIn('cobros_formas_pago.cod_forma_cobro', [2, 3])
                            ->sum('cobros_formas_pago.monto') ?? 0;
                        $trSistema = $record->cobros()
                            ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                            ->where('cobros_formas_pago.cod_forma_cobro', 4)
                            ->sum('cobros_formas_pago.monto') ?? 0;
                        $chSistema = $record->cobros()
                            ->join('cobros_formas_pago', 'cobros.cod_cobro', '=', 'cobros_formas_pago.cod_cobro')
                            ->where('cobros_formas_pago.cod_forma_cobro', 5)
                            ->sum('cobros_formas_pago.monto') ?? 0;
                        $totalSistema = $efSistema + $tjSistema + $trSistema + $chSistema;

                        $diferencia = $totalFisico - $totalSistema;

                        ArqueoCaja::create([
                            'cod_apertura' => $record->cod_apertura,
                            'efectivo_sistema' => $efSistema,
                            'tarjetas_sistema' => $tjSistema,
                            'transferencias_sistema' => $trSistema,
                            'cheques_sistema' => $chSistema,
                            'total_sistema' => $totalSistema,
                            'efectivo_fisico' => $efFisico,
                            'tarjetas_fisico' => $tjFisico,
                            'transferencias_fisico' => $trFisico,
                            'cheques_fisico' => $chFisico,
                            'total_fisico' => $totalFisico,
                            'diferencia' => $diferencia,
                            'usuario_alta' => Auth::user()->name,
                            'fecha_alta' => now(),
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Arqueo registrado')
                            ->body('El conteo físico fue guardado. La caja sigue abierta. Ahora puede cerrarla definitivamente.')
                            ->send();
                    }),

                Tables\Actions\EditAction::make()
                    ->label('Cerrar')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (AperturaCaja $record): bool => $record->estado === 'Abierta' && $record->arqueos()->count() > 0),

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
