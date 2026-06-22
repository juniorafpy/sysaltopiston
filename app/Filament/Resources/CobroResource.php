<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CobroResource\Pages;
use App\Models\Cobro;
use App\Models\Factura;
use App\Models\EntidadBancaria;
use App\Models\TipoTarjeta;
use App\Models\FormaCobro;
use App\Models\Procesadora;
use App\Models\Personas;
use App\Models\AperturaCaja;
use App\Forms\Components\FacturasSelector;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Auth;

class CobroResource extends Resource
{
    protected static ?string $model = Cobro::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'Cobros';
    protected static ?string $navigationGroup = 'Gestión Ventas';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ═══ ENCABEZADO ═══
                Forms\Components\Section::make('Datos del Cobro')
                    ->icon('heroicon-o-document-text')
                    ->compact()
                    ->schema([
                        Forms\Components\Grid::make(4)->schema([
                            Forms\Components\Select::make('cod_cliente')
                                ->label('Cliente')
                                ->options(function () {
                                    return Personas::whereHas('facturas', function ($query) {
                                        $query->where('condicion_venta', 'Crédito')
                                              ->where('estado', 'Emitida');
                                    })
                                    ->get()
                                    ->mapWithKeys(fn ($c) => [$c->cod_persona => $c->nombre_completo]);
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (callable $set) {
                                    $set('detalles', []);
                                })
                                ->columnSpan(2)
                                ->prefixIcon('heroicon-o-user'),

                            Forms\Components\DateTimePicker::make('fecha_cobro')
                                ->label('Fecha')
                                ->default(now())
                                ->required()
                                ->maxDate(now())
                                ->native(false)
                                ->displayFormat('d/m/Y H:i')
                                ->locale('es')
                                ->prefixIcon('heroicon-o-calendar'),

                            Forms\Components\Placeholder::make('caja_info')
                                ->label('Caja')
                                ->content(function () {
                                    $user = Auth::user();
                                    $apertura = AperturaCaja::where('usuario', $user->name)
                                        ->where('estado', 'Abierta')
                                        ->first();
                                    if ($apertura) {
                                        $caja = $apertura->caja;
                                        return new \Illuminate\Support\HtmlString(
                                            '<div class="flex items-center gap-2 text-success-600">'
                                            . '🔓'
                                            . '<span class="font-medium">' . ($caja->descripcion ?? 'Caja ' . $apertura->cod_caja) . '</span>'
                                            . '</div>'
                                        );
                                    }
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="flex items-center gap-2 text-danger-600">'
                                        . '🔒'
                                        . '<span class="font-medium">Sin caja abierta</span>'
                                        . '</div>'
                                    );
                                }),
                        ]),
                    ]),

                // ═══ FACTURAS + RESUMEN ═══
                Forms\Components\Grid::make(10)->schema([

                    // Facturas a Cobrar
                    Forms\Components\Section::make('Facturas a Cobrar')
                        ->icon('heroicon-o-document-text')
                        ->columnSpan(7)
                        ->schema([
                            FacturasSelector::make('detalles')
                                ->label(''),
                        ])
                        ->headerActions([
                            Forms\Components\Actions\Action::make('total_facturas')
                                ->label(fn (Get $get) => 'Total: Gs. ' . number_format(collect($get('detalles') ?? [])->sum(fn ($i) => (float) ($i['monto_cuota'] ?? 0)), 0, ',', '.'))
                                ->color('success')
                                ->disabled(),
                        ]),

                    // Resumen del Cobro
                    Forms\Components\Section::make('Resumen')
                        ->icon('heroicon-o-calculator')
                        ->columnSpan(3)
                        ->schema([
                            Forms\Components\Placeholder::make('total_cobrar')
                                ->label('Total a Cobrar')
                                ->content(function (Get $get) {
                                    $total = collect($get('detalles') ?? [])->sum(fn ($i) => (float) ($i['monto_cuota'] ?? 0));
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="flex items-center justify-between p-3 bg-success-50 rounded-lg border border-success-200">'
                                        . '<span class="text-sm text-success-700 font-medium">Total</span>'
                                        . '<span class="text-2xl font-bold text-success-600">Gs. ' . number_format($total, 0, ',', '.') . '</span>'
                                        . '</div>'
                                    );
                                }),

                            Forms\Components\Placeholder::make('total_recibido')
                                ->label('Total Recibido')
                                ->content(function (Get $get) {
                                    $total = collect($get('formas_pago') ?? [])->sum(fn ($i) => (float) ($i['monto'] ?? 0));
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="flex items-center justify-between p-3 bg-primary-50 rounded-lg border border-primary-200">'
                                        . '<span class="text-sm text-primary-700 font-medium">Recibido</span>'
                                        . '<span class="text-2xl font-bold text-primary-600">Gs. ' . number_format($total, 0, ',', '.') . '</span>'
                                        . '</div>'
                                    );
                                }),

                            Forms\Components\Placeholder::make('diferencia')
                                ->label('Diferencia')
                                ->content(function (Get $get) {
                                    $cobrar = collect($get('detalles') ?? [])->sum(fn ($i) => (float) ($i['monto_cuota'] ?? 0));
                                    $recibido = collect($get('formas_pago') ?? [])->sum(fn ($i) => (float) ($i['monto'] ?? 0));
                                    $diff = $recibido - $cobrar;

                                    if ($diff == 0) {
                                        $color = 'gray';
                                        $label = 'Cancelado';
                                    } elseif ($diff > 0) {
                                        $color = 'warning';
                                        $label = 'Vuelto';
                                    } else {
                                        $color = 'danger';
                                        $label = 'Pendiente';
                                    }

                                    $signo = $diff > 0 ? '+ ' : ($diff < 0 ? '- ' : '');
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="flex items-center justify-between p-3 bg-' . $color . '-50 rounded-lg border border-' . $color . '-200">'
                                        . '<span class="text-sm text-' . $color . '-700 font-medium">' . $label . '</span>'
                                        . '<div class="text-right">'
                                        . '<span class="text-xl font-bold text-' . $color . '-600">' . $signo . 'Gs. ' . number_format(abs($diff), 0, ',', '.') . '</span>'
                                        . ($diff < 0 ? '<div class="text-xs text-' . $color . '-500">Falta cobrar</div>' : '')
                                        . ($diff > 0 ? '<div class="text-xs text-' . $color . '-500">Vuelto al cliente</div>' : '')
                                        . '</div>'
                                        . '</div>'
                                    );
                                }),
                        ])
                        ->compact(),
                ]),

                // ═══ FORMAS DE PAGO ═══
                Forms\Components\Section::make('Formas de Pago')
                    ->icon('heroicon-o-credit-card')
                    ->compact()
                    ->schema([
                        Forms\Components\Repeater::make('formas_pago')
                            ->label('')
                            ->schema([
                                Forms\Components\Select::make('cod_forma_cobro')
                                    ->label('')
                                    ->options(FormaCobro::orderBy('cod_forma_cobro')->pluck('descripcion', 'cod_forma_cobro'))
                                    ->required()
                                    ->live()
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(6)->schema([
                                    Forms\Components\TextInput::make('monto')
                                        ->label('Monto')
                                        ->numeric()
                                        ->required()
                                        ->minValue(1)
                                        ->prefix('Gs.')
                                        ->live()
                                        ->columnSpan(2),

                                    Forms\Components\Select::make('cod_entidad_bancaria')
                                        ->label('Banco')
                                        ->options(EntidadBancaria::activas()->orderBy('nombre')->pluck('nombre', 'cod_entidad_bancaria'))
                                        ->searchable()
                                        ->preload()
                                        ->visible(fn (Get $get): bool => in_array($get('cod_forma_cobro'), [2, 3, 4]))
                                        ->columnSpan(2)
                                        ->prefixIcon('heroicon-o-building-library'),

                                    Forms\Components\Select::make('cod_tipo_tarjeta')
                                        ->label('Tipo Tarjeta')
                                        ->options(TipoTarjeta::orderBy('descripcion')->pluck('descripcion', 'cod_tipo_tarjeta'))
                                        ->searchable()
                                        ->preload()
                                        ->visible(fn (Get $get): bool => in_array($get('cod_forma_cobro'), [2, 3]))
                                        ->columnSpan(2)
                                        ->prefixIcon('heroicon-o-credit-card'),

                                    Forms\Components\Select::make('cod_procesadora')
                                        ->label('Procesadora')
                                        ->options(Procesadora::orderBy('descripcion')->pluck('descripcion', 'cod_procesadora'))
                                        ->searchable()
                                        ->preload()
                                        ->visible(fn (Get $get): bool => in_array($get('cod_forma_cobro'), [2, 3]))
                                        ->columnSpan(2)
                                        ->prefixIcon('heroicon-o-globe-alt'),

                                    Forms\Components\TextInput::make('numero_voucher')
                                        ->label('Nº Voucher')
                                        ->visible(fn (Get $get): bool => in_array($get('cod_forma_cobro'), [2, 3, 4]))
                                        ->columnSpan(2)
                                        ->prefixIcon('heroicon-o-hashtag'),
                                ]),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('+ Agregar Pago')
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información General del Cobro')
                    ->schema([
                        Infolists\Components\TextEntry::make('cod_cobro')
                            ->label('N° Cobro'),
                        Infolists\Components\TextEntry::make('fecha_cobro')
                            ->label('Fecha')
                            ->date('d/m/Y'),
                        Infolists\Components\TextEntry::make('cliente.nombre_completo')
                            ->label('Cliente'),
                        Infolists\Components\TextEntry::make('aperturaCaja.cod_apertura')
                            ->label('Apertura de Caja'),
                        Infolists\Components\TextEntry::make('estado')
                            ->label('Estado')
                            ->badge()
                            ->color(fn (?string $state): string => match ($state) {
                                'C' => 'success',
                                'A' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'C' => 'Confirmado',
                                'A' => 'Anulado',
                                default => $state ?? '-',
                            }),
                        Infolists\Components\TextEntry::make('monto_total')
                            ->label('Monto Total')
                            ->money('PYG', divideBy: 1)
                            ->weight('bold')
                            ->size('lg'),
                    ])
                    ->columns(3),

                Infolists\Components\Section::make('Facturas y Cuotas Cobradas')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('detalles')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('factura.numero_factura')
                                    ->label('Factura'),
                                Infolists\Components\TextEntry::make('numero_cuota')
                                    ->label('N° Cuota')
                                    ->badge()
                                    ->color('info'),
                                Infolists\Components\TextEntry::make('monto_cuota')
                                    ->label('Monto')
                                    ->money('PYG', divideBy: 1),
                                Infolists\Components\TextEntry::make('factura.condicionCompra.descripcion')
                                    ->label('Condición'),
                            ])
                            ->columns(4),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Formas de Pago Utilizadas')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('formasPago')
                            ->label('')
                            ->schema([
                                Infolists\Components\TextEntry::make('formaCobro.descripcion')
                                    ->label('Tipo')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'Efectivo' => 'success',
                                        'Tarjeta de Crédito' => 'warning',
                                        'Tarjeta de Débito' => 'info',
                                        'Cheque' => 'danger',
                                        'Transferencia' => 'primary',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('monto')
                                    ->label('Monto')
                                    ->money('PYG', divideBy: 1),
                                Infolists\Components\TextEntry::make('entidadBancaria.nombre')
                                    ->label('Banco')
                                    ->visible(fn ($record) => $record->cod_entidad_bancaria !== null),
                                Infolists\Components\TextEntry::make('tipoTarjeta.descripcion')
                                    ->label('Tipo Tarjeta')
                                    ->visible(fn ($record) => $record->cod_tipo_tarjeta !== null),
                                Infolists\Components\TextEntry::make('procesadora.descripcion')
                                    ->label('Procesadora')
                                    ->visible(fn ($record) => $record->cod_procesadora !== null),
                                Infolists\Components\TextEntry::make('numero_voucher')
                                    ->label('N° Voucher')
                                    ->visible(fn ($record) => $record->numero_voucher !== null),
                                Infolists\Components\TextEntry::make('numero_cheque')
                                    ->label('N° Cheque')
                                    ->visible(fn ($record) => $record->numero_cheque !== null),
                            ])
                            ->columns(5),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Información de Registro')
                    ->schema([
                        Infolists\Components\TextEntry::make('usuario_alta')
                            ->label('Registrado por'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Fecha de Registro')
                            ->dateTime('d/m/Y H:i:s'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_cobro')->label('N°')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('fecha_cobro')->label('Fecha')->date('d/m/Y')->sortable(),
                Tables\Columns\TextColumn::make('cliente.nombre_completo')->label('Cliente')->searchable()->limit(30),
                Tables\Columns\TextColumn::make('monto_total')->label('Monto')->money('PYG', divideBy: 1)->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'C' => 'success',
                        'A' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'C' => 'Confirmado',
                        'A' => 'Anulado',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('usuario_alta')->label('Usuario'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Anular Cobro')
                    ->modalDescription('¿Está seguro de anular este cobro? Se revertirá el saldo de las cuotas y se registrará un movimiento de egreso en caja.')
                    ->modalSubmitActionLabel('Sí, Anular')
                    ->visible(fn (Cobro $record): bool => $record->estado !== 'A')
                    ->action(function (Cobro $record) {
                        $record->update(['estado' => 'A']);
                    })
                    ->after(function () {
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Cobro anulado')
                            ->body('El cobro ha sido anulado exitosamente.')
                            ->send();
                    }),
            ])
            ->defaultSort('fecha_cobro', 'desc');
    }

    public static function getRelations(): array { return []; }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCobros::route('/'),
            'create' => Pages\CreateCobro::route('/create'),
        ];
    }
}
