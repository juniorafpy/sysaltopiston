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


class PedidoCabeceraResource extends Resource
{
   // protected static ?string $model = PedidoCabeceras::class;
    protected static ?string $model = PedidoCabeceras::class;
  //  protected static ?string $model = PedidoCabecera::class;
    protected static ?string $navigationLabel = 'Pedido de Compra';

    protected static ?string $title = 'Lista de Compra';
    protected static ?int $navigationSort = 1;

    protected static ?string $navidngationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {



        return $form
            ->schema([
               // 🧾 CABECERA
            Section::make('Datos del Pedido')
                ->schema([

            Forms\Components\Hidden::make('cod_empleado'),
            Forms\Components\TextInput::make('nombre_empleado')
                ->label('Empleado')
                ->disabled()
                ->dehydrated(false),

            Forms\Components\Hidden::make('cod_sucursal'),

             // Campo de TEXTO para mostrar el nombre
            Forms\Components\TextInput::make('nombre_sucursal')
                ->label('Sucursal')
                ->disabled()
                ->dehydrated(false),

           DatePicker::make('fec_pedido')
    ->label('Fecha Pedido')

    // Aquí está el cambio: usamos Carbon explícitamente
    ->default(Carbon::now('America/Asuncion'))
    ->displayFormat('d/m/Y') // Muestra en formato dd/mm/yyyy
    ->native(false)          // Usa el picker de Filament para asegurar el formato
    ->required(),         // Requerido para que displayFormat funcione en todos los navegadores

                    TextInput::make('usuario_alta')
                        ->disabled()
                        ->label('Usuario Alta'),

           Placeholder::make('fec_alta_display') // Dale un nombre único que no sea de una columna
    ->label('Fecha Alta')
    ->content(function () {
        // Formateamos la fecha actual de Paraguay como un string
        return Carbon::now('America/Asuncion')->format('d/m/Y');
    }),



                ])


                ->columns(2),

Section::make('Detalle de Pedido')
    ->schema([
       TableRepeater::make('detalles')->label('')
       ->relationship('detalles')
                ->schema([
                    Select::make('cod_articulo')
                        ->label('Producto')
                        ->relationship('articulos_det', 'descripcion')
                        ->searchable()
                        ->preload()
                        ->required()
                         ->columns(2),

                    TextInput::make('cantidad')
                        ->label('Cantidad')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->required()
                        ->columns(1),

                ]) ->required()

            ->columns(3)
            //->defaultItems(1),
    ])

    ->compact(),
 ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_pedido')->label('Nº Pedido'),
                Tables\Columns\TextColumn::make('fec_pedido')->label('Fecha Pedido')->date('d/m/Y'),
                Tables\Columns\TextColumn::make('ped_empleados.personas_emp.nombre_completo')->label('Empleado'),
                Tables\Columns\TextColumn::make('sucursal_ped.descripcion')->label('Sucursal'),
                Tables\Columns\TextColumn::make('estado')

            ])

            ->filters([
                //
            ])


            ->actions([
                Tables\Actions\EditAction::make(),

                // --- NUESTRA NUEVA ACCIÓN DE ANULAR ---
            Action::make('anular')
                ->label('Anular')
                ->icon('heroicon-o-x-circle')
                ->color('danger') // Color rojo para indicar una acción importante
                ->requiresConfirmation() // Pide confirmación antes de ejecutar
                ->modalHeading('Anular Pedido')
                ->modalDescription('¿Estás seguro de que deseas anular este pedido? Esta acción no se puede deshacer.')
                ->modalSubmitActionLabel('Sí, anular')
                ->action(function (Model $record) {
                    // La lógica que se ejecuta al confirmar
                    $record->update(['estado' => 'ANULADO']);
                    Notification::make()
                        ->title('Pedido Anulado')
                        ->success()
                        ->send();
                })
                // Hacemos que el botón de Anular solo sea visible si el estado NO es "Anulado"
                ->visible(fn (Model $record): bool => $record->estado !== 'Anulado'),

                Action::make('aprobar')
                ->label('Aprobar')
                ->icon('heroicon-o-x-circle')
                ->color('success') // Color rojo para indicar una acción importante
                ->requiresConfirmation() // Pide confirmación antes de ejecutar
                ->modalHeading('Aprobar Pedido')
                ->modalDescription('¿Estás seguro de que deseas aprobar este pedido? Esta acción no se puede deshacer.')
                ->modalSubmitActionLabel('Sí, aprobar')
                ->action(function (Model $record) {
                    // La lógica que se ejecuta al confirmar
                    $record->update(['estado' => 'APROBADO']);
                    Notification::make()
                        ->title('Pedido Aprobado')
                        ->success()
                        ->send();
                })
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
