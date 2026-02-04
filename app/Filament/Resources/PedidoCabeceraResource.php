<?php

namespace App\Filament\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Sucursal;
use Filament\Forms\Form;
use App\Models\Empleados;
use Filament\Tables\Table;
use App\Models\PedidoCabecera;
use App\Models\ExisteStock;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PedidoCabeceraResource\Pages;
use App\Filament\Resources\PedidoCabeceraResource\RelationManagers;
use App\Models\PedidoCabeceras;
use Icetalker\FilamentTableRepeater\Forms\Components\TableRepeater;
use Filament\Forms\Set;
use Filament\Forms\Get;


class PedidoCabeceraResource extends Resource
{
   // protected static ?string $model = PedidoCabeceras::class;
    protected static ?string $model = PedidoCabeceras::class;
  //  protected static ?string $model = PedidoCabecera::class;

    protected static ?string $navigationGroup = 'Compras';
    protected static ?string $navigationLabel = 'Pedido de Compra';
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Lista de Compra';

    public static function form(Form $form): Form
    {



        return $form
            ->schema([
                // 🧾 CABECERA
                Section::make('Datos del Pedido')
                    ->schema([
                        Select::make('cod_empleado')
                            ->label('Empleado')
                            ->options(function () {
                                return Empleados::with('persona')
                                    ->where('activo', true)
                                    ->get()
                                    ->mapWithKeys(function ($empleado) {
                                        $nombre = $empleado->persona
                                            ? $empleado->persona->nombre_completo
                                            : 'Sin nombre';
                                        return [$empleado->cod_empleado => $nombre];
                                    });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($context) => $context === 'view'),

                        Select::make('cod_sucursal')
                            ->label('Sucursal')
                            ->options(fn () => Sucursal::pluck('descripcion', 'cod_sucursal'))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($context) => $context === 'view'),

                        DatePicker::make('fec_pedido')
                            ->label('Fecha Pedido')
                            ->default(Carbon::now('America/Asuncion'))
                            ->displayFormat('d/m/Y')
                            ->native(false)
                            ->required()
                            ->disabled(fn ($context) => $context === 'view'),
                    ])
                    ->columns(3),

                Section::make('Detalle de Pedido')
    ->schema([
       TableRepeater::make('detalles')->label('')
       ->relationship('detalles')
                ->schema([
                    Select::make('cod_articulo')
                        ->label('Producto')
                        ->relationship('articulo', 'descripcion')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state) {
                                $sucursalId = auth()->user()->cod_sucursal ?? 1;
                                $stock = ExisteStock::where('cod_articulo', $state)
                                    ->where('cod_sucursal', $sucursalId)
                                    ->first();

                                if ($stock) {
                                    $stockDisponible = $stock->stock_actual - $stock->stock_reservado;
                                    $set('stock_disponible', number_format($stockDisponible, 2));
                                } else {
                                    $set('stock_disponible', '0');
                                }
                            } else {
                                $set('stock_disponible', '');
                            }
                        })
                        ->columnSpan(2),

                    TextInput::make('stock_disponible')
                        ->label('Stock Disp.')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('--')
                        ->columnSpan(1),

                    TextInput::make('cantidad')
                        ->label('Cantidad')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->required()
                        ->columnSpan(1),

                ]) ->required()

            ->columns(4)
            //->defaultItems(1),
    ])

    ->compact(),

                Section::make('Información del Sistema')
                    ->schema([
                        TextInput::make('usuario_alta')
                            ->label('Usuario')
                            ->disabled()
                            ->dehydrated(false)
                            ->prefixIcon('heroicon-m-user'),

                        Placeholder::make('fec_alta_display')
                            ->label('Fecha')
                            ->content(fn () => Carbon::now('America/Asuncion')->format('d/m/Y H:i')),
                    ])
                    ->columns(2)
                    ->collapsed(),
 ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_pedido')->label('Nº Pedido'),
                Tables\Columns\TextColumn::make('fec_pedido')
                    ->label('Fecha Pedido')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('ped_empleados.persona.nombre_completo')->label('Empleado'),
                Tables\Columns\TextColumn::make('sucursal_ped.descripcion')->label('Sucursal'),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDIENTE' => 'info',
                        'APROBADO' => 'success',
                        'ANULADO' => 'danger',
                        default => 'gray',
                    })
            ])
            ->defaultSort('fec_pedido', 'desc')

            ->filters([
                //
            ])


            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Ver')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->modalHeading(fn ($record) => 'Pedido N° ' . $record->cod_pedido)
                        ->modalWidth('7xl'),

                    Tables\Actions\EditAction::make()
                        ->visible(fn (Model $record): bool => $record->estado !== 'ANULADO' && $record->estado !== 'APROBADO'),

                    Action::make('aprobar')
                        ->label('Aprobar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Aprobar Pedido')
                        ->modalDescription('¿Estás seguro de que deseas aprobar este pedido? Esta acción no se puede deshacer.')
                        ->modalSubmitActionLabel('Sí, aprobar')
                        ->action(function (Model $record) {
                            $record->update(['estado' => 'APROBADO']);
                            Notification::make()
                                ->title('Pedido Aprobado')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Model $record): bool => $record->estado === 'PENDIENTE'),

                    Action::make('anular')
                        ->label('Anular')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Anular Pedido')
                        ->modalDescription('¿Estás seguro de que deseas anular este pedido? Esta acción no se puede deshacer.')
                        ->modalSubmitActionLabel('Sí, anular')
                        ->action(function (Model $record) {
                            $record->update(['estado' => 'ANULADO']);
                            Notification::make()
                                ->title('Pedido Anulado')
                                ->success()
                                ->send();
                        })
                        ->visible(fn (Model $record): bool => $record->estado === 'PENDIENTE'),
                ])
                ->label('Acciones')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('primary')
                ->button(),
            ]);
            /*->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);*/
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
            'index' => Pages\ListPedidoCabeceras::route('/'),
            'create' => Pages\CreatePedidoCabecera::route('/create'),
            'edit' => Pages\EditPedidoCabecera::route('/{record}/edit'),
        ];
    }
}
