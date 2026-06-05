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
    protected static ?string $navigationGroup = 'Referenciales/Ventas';
    protected static ?string $navigationLabel = 'Clientes';
    protected static ?string $modelLabel = 'Cliente';
    protected static ?string $pluralModelLabel = 'Clientes';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Datos del Cliente')
                ->description('Seleccione la persona que será registrada como cliente')
                ->icon('heroicon-o-user-group')
                ->schema([
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
                        ->validationMessages([
                            'unique' => 'Esta persona ya está registrada como cliente.',
                        ])
                        ->placeholder('Buscar por documento, nombre o razón social...')
                        ->createOptionUsing(function (array $data) {
                            $persona = Personas::create($data);
                            return $persona->cod_persona;
                        })
                        ->columnSpan(2),

                    Forms\Components\Toggle::make('estado')
                        ->label('Estado Activo')
                        ->default(true)
                        ->formatStateUsing(fn ($state) => $state === 'A')
                        ->dehydrateStateUsing(fn ($state) => $state ? 'A' : 'I')
                        ->onIcon('heroicon-o-check-circle')
                        ->offIcon('heroicon-o-x-circle')
                        ->onColor('success')
                        ->offColor('danger')
                        ->helperText('Active para habilitar este cliente')
                        ->columnSpan(1),

                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\TextInput::make('usuario_alta')
                            ->label('Registrado por')
                            ->default(fn () => auth()->user()->name)
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(1),

                        Forms\Components\DatePicker::make('fec_alta')
                            ->label('Fecha de Registro')
                            ->default(now())
                            ->disabled()
                            ->dehydrated()
                            ->displayFormat('d/m/Y')
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
                    ->weight('medium')
                    ->limit(40)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 40) {
                            return $state;
                        }
                        return null;
                    }),

                Tables\Columns\TextColumn::make('persona.email')
                    ->label('Email')
                    ->searchable()
                    ->icon('heroicon-o-envelope')
                    ->limit(30),

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

                Tables\Columns\TextColumn::make('fec_alta')
                    ->label('Fecha Alta')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modal()
                    ->modalSubmitActionLabel('Guardar')
                    ->successNotificationTitle(null)
                    ->before(function (array $data, \Filament\Actions\StaticAction $action, $record) {
                        $existe = Cliente::where('cod_persona', $data['cod_persona'])
                            ->where('cod_cliente', '!=', $record->cod_cliente)
                            ->exists();
                        if ($existe) {
                            $action->getLivewire()->dispatch('swal:error', message: 'Esta persona ya está registrada como cliente.');
                            $action->halt();
                        }
                    })
                    ->after(function ($record, $livewire) {
                        $livewire->dispatch('swal:success', message: 'Cliente actualizado exitosamente.');
                    }),
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
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('estado', 'A')->count();
    }
}
