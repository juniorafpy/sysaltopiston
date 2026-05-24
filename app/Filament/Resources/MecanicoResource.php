<?php

namespace App\Filament\Resources;

use App\Models\Mecanico;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\MecanicoResource\Pages;

class MecanicoResource extends Resource
{
    protected static ?string $model = Mecanico::class;

    protected static ?string $slug = 'mecanicos';

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Referenciales/Servicios';
    protected static ?string $navigationLabel = 'Mecánicos';
    protected static ?string $modelLabel = 'Mecánico';
    protected static ?string $pluralModelLabel = 'Mecánicos';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del Mecánico')
                    ->schema([
                        Forms\Components\Select::make('cod_empleado')
                            ->label('Empleado')
                            ->searchable()
                            ->options(function () {
                                return \App\Models\Empleados::with('persona')
                                    ->whereHas('persona')
                                    ->limit(10)
                                    ->get()
                                    ->mapWithKeys(function ($empleado) {
                                        $persona = $empleado->persona;
                                        if ($persona) {
                                            $nombre = $persona->razon_social ?: trim($persona->nombres . ' ' . $persona->apellidos);
                                            return [$empleado->cod_empleado => "{$empleado->cod_empleado} - {$nombre} ({$persona->nro_documento})"];
                                        }
                                        return [$empleado->cod_empleado => $empleado->cod_empleado];
                                    })
                                    ->toArray();
                            })
                            ->getSearchResultsUsing(function (string $search): array {
                                return \App\Models\Empleados::with('persona')
                                    ->whereHas('persona', function ($query) use ($search) {
                                        $query->where('nombres', 'ilike', "%{$search}%")
                                            ->orWhere('apellidos', 'ilike', "%{$search}%")
                                            ->orWhere('razon_social', 'ilike', "%{$search}%")
                                            ->orWhere('nro_documento', 'ilike', "%{$search}%");
                                    })
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($empleado) {
                                        $persona = $empleado->persona;
                                        if ($persona) {
                                            $nombre = $persona->razon_social ?: trim($persona->nombres . ' ' . $persona->apellidos);
                                            return [$empleado->cod_empleado => "{$empleado->cod_empleado} - {$nombre} ({$persona->nro_documento})"];
                                        }
                                        return [$empleado->cod_empleado => $empleado->cod_empleado];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelUsing(function ($value): ?string {
                                $empleado = \App\Models\Empleados::with('persona')->find($value);
                                if (!$empleado) return null;
                                $persona = $empleado->persona;
                                if ($persona) {
                                    $nombre = $persona->razon_social ?: trim($persona->nombres . ' ' . $persona->apellidos);
                                    return "{$empleado->cod_empleado} - {$nombre} ({$persona->nro_documento})";
                                }
                                return $empleado->cod_empleado;
                            })
                            ->required()
                            ->unique('mecanico', 'cod_empleado', ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Este empleado ya está registrado como mecánico.',
                            ])
                            ->columnSpan(2),

                        Forms\Components\Select::make('cod_especialidad')
                            ->label('Especialidad')
                            ->relationship('especialidad', 'descripcion')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2),

                        Forms\Components\Toggle::make('estado')
                            ->label('Estado')
                            ->default(true)
                            ->formatStateUsing(fn ($state) => $state === 'A')
                            ->dehydrateStateUsing(fn ($state) => $state ? 'A' : 'I'),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('usuario_alta')
                                ->label('Usuario de Registro')
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
                Tables\Columns\TextColumn::make('cod_mecanico')
                    ->label('Código')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('empleado.persona.nro_documento')
                    ->label('CI')
                    ->searchable()
                    ->icon('heroicon-o-identification'),

                Tables\Columns\TextColumn::make('empleado.persona.nombres')
                    ->label('Nombre')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        $persona = $record->empleado?->persona;
                        if ($persona) {
                            return $persona->razon_social ?: trim($persona->nombres . ' ' . $persona->apellidos);
                        }
                        return '-';
                    }),

              /*  Tables\Columns\TextColumn::make('empleado.cargo.descripcion')
                    ->label('Cargo')
                    ->badge()
                    ->color('info'),*/

                Tables\Columns\TextColumn::make('especialidad.descripcion')
                    ->label('Especialidad')
                    ->badge()
                    ->color('success'),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'A' ? 'Activo' : 'Inactivo')
                    ->colors([
                        'success' => 'A',
                        'danger' => 'I',
                    ]),

                Tables\Columns\TextColumn::make('usuario_alta')
                    ->label('Usuario Alta'),

                Tables\Columns\TextColumn::make('fec_alta')
                    ->label('Fecha Alta')
                    ->dateTime()
                    ->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->format('d/m/Y')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modal()
                    ->modalSubmitActionLabel('Guardar')
                    ->successNotificationTitle(null)
                    ->after(function ($record, $livewire) {
                        $livewire->dispatch('swal:success', message: 'Mecánico actualizado exitosamente.');
                    }),
                Tables\Actions\DeleteAction::make()
                    ->successNotificationTitle(null)
                    ->after(function ($record, $livewire) {
                        $livewire->dispatch('swal:success', message: 'Mecánico eliminado exitosamente.');
                    }),
            ])
            ->defaultSort('cod_mecanico', 'asc');
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
            'index' => Pages\ListMecanicos::route('/'),
        ];
    }
}
