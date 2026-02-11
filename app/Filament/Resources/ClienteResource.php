<?php

namespace App\Filament\Resources;

use App\Models\Cliente;
use App\Models\Personas;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Filament\Resources\ClienteResource\Pages;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $slug = 'clientes';

    protected static ?string $recordTitleAttribute = 'cod_cliente';

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Definiciones';
    protected static ?string $navigationLabel = 'Clientes';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del Cliente')
                ->description('Seleccione la persona y configure el estado del cliente')
                ->icon('heroicon-o-user-group')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('cod_persona')
                            ->label('Persona')
                            ->relationship('persona', 'nro_documento')
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                $nombre = $record->razon_social ?: trim($record->nombres . ' ' . $record->apellidos);
                                return "{$record->nro_documento} - {$nombre}";
                            })
                            ->searchable(['nro_documento', 'nombres', 'apellidos', 'razon_social'])
                            ->preload()
                            ->required()
                            ->unique('clientes', 'cod_persona', ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Esta persona ya está registrada como cliente.',
                            ])
                            ->helperText('Seleccione la persona que será registrada como cliente')
                            ->createOptionUsing(function (array $data) {
                                $persona = Personas::create($data);
                                return $persona->cod_persona;
                            })
                            ->columnSpan(2),

                        Forms\Components\Select::make('estado')
                            ->label('Estado')
                            ->options([
                                'A' => 'Activo',
                                'I' => 'Inactivo',
                            ])
                            ->default('A')
                            ->required()
                            ->native(false)
                            ->columnSpan(1),
                    ]),
                ]),

            Forms\Components\Section::make('Información del Sistema')
                ->description('Datos de auditoría')
                ->icon('heroicon-o-clock')
                ->collapsed()
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('usuario_alta')
                            ->label('Usuario de Registro')
                            ->default(fn () => auth()->user()->name)
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('fec_alta')
                            ->label('Fecha de Registro')
                            ->default(now()->toDateTimeString())
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_cliente')
                    ->label('#')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('persona.nro_documento')
                    ->label('CI/RUC')
                    ->searchable()
                    ->icon('heroicon-o-identification')
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Nombre / Razón Social')
                    ->searchable(['persona.nombres', 'persona.apellidos', 'persona.razon_social'])
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('persona.email')
                    ->label('Email')
                    ->searchable()
                    ->icon('heroicon-o-envelope'),

                Tables\Columns\TextColumn::make('persona.direccion')
                    ->label('Dirección')
                    ->limit(30)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 30) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\BadgeColumn::make('estado')
                    ->label('Estado')
                    ->colors([
                        'success' => 'A',
                        'danger' => 'I',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'A' => 'Activo',
                        'I' => 'Inactivo',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('usuario_alta')
                    ->label('Registrado por'),

                Tables\Columns\TextColumn::make('fec_alta')
                    ->label('Fecha Alta')
                    ->dateTime('d/m/Y H:i')

            ])
            ->actions([
                Tables\Actions\EditAction::make()
            ])
            ->defaultSort('cod_cliente', 'desc');
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
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
            'view' => Pages\ViewCliente::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('estado', 'A')->count();
    }
}
