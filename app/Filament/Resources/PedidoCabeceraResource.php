<?php

namespace App\Filament\Resources;

use Filament\Forms;

use Filament\Tables;
use App\Models\Sucursal;
use Filament\Forms\Form;
use App\Models\Empleados;
use Filament\Tables\Table;
use App\Models\PedidoCabecera;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\PedidoCabeceraResource\Pages;
use Filament\Resources\RelationManagers\RelationManager;

class PedidoCabeceraResource extends Resource
{
    protected static ?string $model = PedidoCabecera::class;

    protected static ?string $navigationGroup = 'Compras';
    protected static ?string $navigationLabel = 'Pedidos Compra';

    protected static ?string $title = 'Pedidos Compra';

    protected static ?string $navigationIcon = 'heroicon-o-document-check';

    public static function form(Form $form): Form
    {
        $user = Auth::user();
        $empleado = Empleados::where('cod_persona', $user->cod_persona)
            ->with('personas') // Asegurar que se carga la relación
            ->first();

        return $form->schema([
            Forms\Components\DateTimePicker::make('fec_pedido')->default(now())
            ->readOnly(), // Fecha actual,

            /*  Forms\Components\Select::make('cod_empleado')

                    ->relationship('ped_empleados', 'nombre') // Nombre de la relación en el modelo PedidoCabecera
                    ->searchable()
                    ->required()
                    ->preload()

                    ->createOptionForm([
                        Forms\Components\DatePicker::make('fec_alta'),
                        Forms\Components\TextInput::make('cod_persona'),

                        Forms\Components\TextInput::make('cod_cargo'),

                        Forms\Components\TextInput::make('nombre'),
                    ]),*/


                    Forms\Components\Hidden::make('usuario_alta')
                ->default(fn() => auth()->user()->name) //asigna automaticamente el usuario
                ->label('Usuario Alta'),

            Forms\Components\Hidden::make('fec_alta')->default(now()->toDateTimeString()), // Fecha actual,


            Forms\Components\Hidden::make('cod_empleado') // Sin "empleado."
                ->label('Código Empleado')
                ->default(fn() => old('cod_empleado') ?? Empleados::where('cod_persona', Auth::user()->cod_persona)->value('cod_empleado'))
                ->dehydrated(true),


                Forms\Components\TextInput::make('nom_empleado')
                ->label('Nombre del Empleado')
                ->formatStateUsing(function ($state, $record) {
                    // Si el registro ya tiene un valor, úsalo (en caso de edición)
                    if ($record && $record->id_empleado) {
                        return optional($record->empleado?->personas)->nombres . ' ' . optional($record->empleado?->personas)->apellidos ?? 'No disponible';
                    }

                    // Si es un nuevo registro, obtener el empleado relacionado con el usuario autenticado
                    return optional(
                        Empleados::where('cod_persona', Auth::user()->cod_persona)
                            ->with('personas')
                            ->first()
                    )->personas?->nombres . ' ' .
                        optional(
                            Empleados::where('cod_persona', Auth::user()->cod_persona)
                                ->with('personas')
                                ->first()
                        )->personas?->apellidos ?? 'No disponible';
                })
                ->disabled(), // Hace que el campo sea solo lectura



                Forms\Components\Hidden::make('cod_sucursal') // Sin "empleado."
                ->label('Cod Sucursal')
                ->formatStateUsing(fn() => Auth::user()->cod_sucursal)
                ->dehydrated(true),


                Forms\Components\TextInput::make('nombre_sucursal') // Campo para mostrar el nombre de la sucursal
                ->label('Nombre de Sucursal')
                ->formatStateUsing(fn() => optional(Sucursal::find(Auth::user()->cod_sucursal))->descripcion ?? 'No disponible') // Obtener el nombre de la sucursal relacionada al usuario
                ->disabled(),




        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
            Tables\Columns\TextColumn::make('cod_pedido'),
            Tables\Columns\TextColumn::make('cod_empleado'),
            Tables\Columns\TextColumn::make('ped_empleados.personas.nombres')->label('Nombre')
            ->getStateUsing(fn ($record) => $record->ped_empleados->personas->nombres . ', ' . $record->ped_empleados->personas->apellidos),
            Tables\Columns\TextColumn::make('fec_pedido')->dateTime('d/m/Y H:i:s'),
            Tables\Columns\TextColumn::make('usuario_alta'),
            //Tables\Columns\TextColumn::make('fec_alta'),
            Tables\Columns\TextColumn::make('estado')->label('Estado')
            ->label('Estado')
            ->getStateUsing(fn ($record) => $record->estado === 'A' ? 'Anulado' : 'Activo') // Traduce 'A' a 'Anulado'
            ->badge() // Hace que se muestre como un badge
            ->color(fn ($record) => $record->estado === 'A' ? 'danger' : 'success'),

            ])

        ->actions([
            Tables\Actions\EditAction::make()
            ->disabled(fn ($record) => $record->estado === 'A'),

                //anular Pedido
            Tables\Actions\Action::make('estado')
            ->label('Anular') // Nombre del botón
           ->icon('heroicon-o-x-circle') // Ícono opcional
            ->color('danger') // Color amarillo para indicar advertencia
            ->requiresConfirmation() // Pide confirmación antes de anular
            ->modalHeading('Anular Pedido') // Personaliza el título del modal
            ->modalDescription('¿Estás seguro de que deseas anular este pedido? Esta acción no se puede deshacer.') // Mensaje de confirmación
            ->modalButton('Sí, anular') // Personaliza el botón de confirmación
            ->action(fn ($record) => $record->update(['estado' => 'A']))
            ->disabled(fn ($record) => $record->estado === 'A'),


            Tables\Actions\Action::make('ver_pedido')
            ->label('Ver')
            ->icon('heroicon-o-eye')
            ->color('info')
            ->url(fn ($record) => PedidoCabeceraResource::getUrl('show', ['record' => $record]))
            ->disabled(fn ($record) => $record->estado === 'A'),
        ]);

    }

    public static function getRelations(): array
    {
        return [\App\Filament\Resources\PedidoCabeceraResource\RelationManagers\PedidoDetalleRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPedidoCabeceras::route('/'),
            'create' => Pages\CreatePedidoCabecera::route('/create'),
            'edit' => Pages\EditPedidoCabecera::route('/{record}/edit'),
            'show' => Pages\ShowPedidoCabecera::route('/{record}'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
{
    if ($data['estado'] === 'A') {
        abort(403, 'No puedes editar un pedido anulado.');
    }

    return $data;
}

    /* public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()->whereHas('empleado', function ($query) {
        $query->where('cod_persona', Auth::user()->id_persona);
    });
}*/
}
