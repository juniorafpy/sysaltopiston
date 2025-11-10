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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Get;

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
        return $form
            ->schema([
                // Sección 1: Información de Apertura
                Forms\Components\Section::make('Información de Apertura')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('cod_caja')
                                    ->label('Caja')
                                    ->options(function () {
                                        return Caja::activas()
                                            ->get()
                                            ->pluck('descripcion', 'cod_caja');
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn (?AperturaCaja $record) => $record !== null)
                                    ->helperText(fn (?AperturaCaja $record) => $record === null ? 'Seleccione la caja a abrir' : null)
                                    ->rules([
                                        fn (): \Closure => function (string $attribute, $value, \Closure $fail) {
                                            if (AperturaCaja::cajaEstaAbierta($value)) {
                                                $fail('Esta caja ya está abierta. Debe cerrarse primero.');
                                            }
                                        },
                                    ]),

                                Forms\Components\Select::make('cod_cajero')
                                    ->label('Cajero (Empleado)')
                                    ->relationship('cajero', 'cod_empleado')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->persona->nombre_completo ?? 'Sin nombre')
                                    ->default(function () {
                                        return Auth::user()->empleado?->cod_empleado;
                                    })
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->disabled(fn (?AperturaCaja $record) => $record !== null)
                                    ->helperText(function () {
                                        $user = Auth::user();
                                        if (!$user->empleado) {
                                            return '⚠️ Tu usuario no tiene un empleado asociado. Edita tu usuario primero.';
                                        }
                                        return 'Empleado que opera la caja';
                                    }),

                                Forms\Components\Hidden::make('cod_sucursal')
                                    ->default(Auth::user()->cod_sucursal ?? null),

                                Forms\Components\DatePicker::make('fecha_apertura')
                                    ->label('Fecha de Apertura')
                                    ->default(now()->toDateString())
                                    ->required()
                                    ->disabled(fn (?AperturaCaja $record) => $record !== null)
                                    ->displayFormat('d/m/Y')
                                    ->dehydrated(),

                                Forms\Components\TimePicker::make('hora_apertura')
                                    ->label('Hora de Apertura')
                                    ->default(now()->format('H:i:s'))
                                    ->required()
                                    ->disabled(fn (?AperturaCaja $record) => $record !== null)
                                    ->seconds(false)
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('monto_inicial')
                                    ->label('Monto Inicial (Efectivo)')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->suffix('Gs.')
                                    ->disabled(fn (?AperturaCaja $record) => $record !== null)
                                    ->helperText('Efectivo con el que se abre la caja'),

                                Forms\Components\Textarea::make('observaciones_apertura')
                                    ->label('Observaciones de Apertura')
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->disabled(fn (?AperturaCaja $record) => $record !== null)
                                    ->maxLength(1000),
                            ]),
                    ])
                    ->collapsible()
                    ->collapsed(fn (?AperturaCaja $record) => $record !== null && $record->estado === 'Cerrada'),

                // Sección 2: Resumen de Movimientos (solo en edición y si está abierta)
                Forms\Components\Section::make('Resumen de Movimientos del Día')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('total_ingresos')
                                    ->label('Total Ingresos')
                                    ->content(fn (?AperturaCaja $record) => $record ? number_format($record->total_ingresos, 0, ',', '.') . ' Gs.' : '0 Gs.'),

                                Forms\Components\Placeholder::make('total_egresos')
                                    ->label('Total Egresos')
                                    ->content(fn (?AperturaCaja $record) => $record ? number_format($record->total_egresos, 0, ',', '.') . ' Gs.' : '0 Gs.'),

                                Forms\Components\Placeholder::make('saldo_esperado_calc')
                                    ->label('Saldo Esperado (Sistema)')
                                    ->content(fn (?AperturaCaja $record) => $record ? number_format($record->saldo_esperado_calculado, 0, ',', '.') . ' Gs.' : '0 Gs.')
                                    ->hint('Monto Inicial + Ingresos - Egresos')
                                    ->hintColor('info'),
                            ]),
                    ])
                    ->visible(fn (?AperturaCaja $record) => $record !== null)
                    ->collapsible()
                    ->collapsed(false),

                // Sección 3: Cierre de Caja (solo en edición y si está abierta)
                Forms\Components\Section::make('Cierre de Caja')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('efectivo_real')
                                    ->label('Efectivo Real Contado')
                                    ->required(fn (?AperturaCaja $record) => $record !== null && $record->estado === 'Abierta')
                                    ->numeric()
                                    ->suffix('Gs.')
                                    ->helperText('Ingrese el efectivo físicamente contado en caja')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Get $get, ?AperturaCaja $record) {
                                        if ($state && $record) {
                                            $saldoEsperado = $record->saldo_esperado_calculado;
                                            $diferencia = $state - $saldoEsperado;
                                            $set('diferencia', $diferencia);
                                            $set('saldo_esperado', $saldoEsperado);

                                            // Monto a depositar = efectivo_real - monto_inicial
                                            $montoDepositar = $state - $record->monto_inicial;
                                            $set('monto_depositar', max(0, $montoDepositar));
                                        }
                                    }),

                                Forms\Components\Placeholder::make('diferencia_display')
                                    ->label('Diferencia')
                                    ->content(function (Get $get, ?AperturaCaja $record) {
                                        $diferencia = $get('diferencia');
                                        if (!$diferencia) return '0 Gs.';

                                        $texto = number_format(abs($diferencia), 0, ',', '.') . ' Gs.';
                                        if ($diferencia > 0) {
                                            return '+ ' . $texto . ' (Sobrante)';
                                        } elseif ($diferencia < 0) {
                                            return '- ' . $texto . ' (Faltante)';
                                        }
                                        return $texto . ' (OK)';
                                    })
                                    ->hint(fn (Get $get) => $get('diferencia') != 0 ? 'Verificar arqueo' : 'Caja cuadrada')
                                    ->hintColor(fn (Get $get) => $get('diferencia') != 0 ? 'warning' : 'success'),

                                Forms\Components\Hidden::make('diferencia'),
                                Forms\Components\Hidden::make('saldo_esperado'),

                                Forms\Components\TextInput::make('monto_depositar')
                                    ->label('Monto a Depositar')
                                    ->numeric()
                                    ->suffix('Gs.')
                                    ->disabled()
                                    ->helperText('Monto que excede el fondo inicial')
                                    ->dehydrated(),

                                Forms\Components\DatePicker::make('fecha_cierre')
                                    ->label('Fecha de Cierre')
                                    ->default(now())
                                    ->disabled()
                                    ->displayFormat('d/m/Y')
                                    ->dehydrated(),

                                Forms\Components\TimePicker::make('hora_cierre')
                                    ->label('Hora de Cierre')
                                    ->default(now()->format('H:i'))
                                    ->disabled()
                                    ->seconds(false)
                                    ->dehydrated(),

                                Forms\Components\Textarea::make('observaciones_cierre')
                                    ->label('Observaciones de Cierre')
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->maxLength(1000)
                                    ->helperText('Indique si hubo diferencias, incidentes u observaciones importantes'),
                            ]),
                    ])
                    ->visible(fn (?AperturaCaja $record) => $record !== null && $record->estado === 'Abierta')
                    ->collapsible()
                    ->collapsed(false),

                // Sección 4: Auditoría (solo lectura, solo en edición)
                Forms\Components\Section::make('Auditoría')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Placeholder::make('usuario_alta_nombre')
                                    ->label('Usuario Apertura')
                                    ->content(fn (?AperturaCaja $record) => $record?->usuarioAlta?->name ?? 'N/A'),

                                Forms\Components\Placeholder::make('fecha_alta_formato')
                                    ->label('Fecha/Hora Alta')
                                    ->content(fn (?AperturaCaja $record) => $record?->fecha_alta?->format('d/m/Y H:i') ?? 'N/A'),

                                Forms\Components\Placeholder::make('estado')
                                    ->label('Estado')
                                    ->content(fn (?AperturaCaja $record) => $record?->estado ?? 'N/A'),
                            ]),
                    ])
                    ->visible(fn (?AperturaCaja $record) => $record !== null)
                    ->collapsible()
                    ->collapsed(true),
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

                Tables\Columns\TextColumn::make('cajero.persona.nombre_completo')
                    ->label('Cajero')
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
                    ->placeholder('Pendiente')
                    ->default(null),

                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'success' => 'Cerrada',
                        'warning' => 'Abierta',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('diferencia')
                    ->label('Diferencia')
                    ->money('PYG', divideBy: 1)
                    ->color(fn ($state) => $state == 0 ? 'success' : ($state > 0 ? 'warning' : 'danger'))
                    ->placeholder('-')
                    ->default(null)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'Abierta' => 'Abierta',
                        'Cerrada' => 'Cerrada',
                    ]),

                Tables\Filters\SelectFilter::make('cod_caja')
                    ->label('Caja')
                    ->options(Caja::activas()->pluck('descripcion', 'cod_caja')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->label('Cerrar')
                    ->icon('heroicon-o-lock-closed')
                    ->color('danger')
                    ->visible(fn (AperturaCaja $record) => $record->estado === 'Abierta'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    // No permitir eliminación masiva de aperturas
                ]),
            ])
            ->defaultSort('fecha_apertura', 'desc');
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
            'index' => Pages\ListAperturasCaja::route('/'),
            'create' => Pages\CreateAperturaCaja::route('/create'),
            'view' => Pages\ViewAperturaCaja::route('/{record}'),
            'edit' => Pages\EditAperturaCaja::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $abiertas = AperturaCaja::abiertas()->count();
        return $abiertas > 0 ? (string) $abiertas : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
