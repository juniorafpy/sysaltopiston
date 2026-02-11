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
    protected static ?string $navigationGroup = 'Definiciones';
    protected static ?string $navigationLabel = 'Mecánicos';
    protected static ?string $modelLabel = 'Mecánico';
    protected static ?string $pluralModelLabel = 'Mecánicos';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información del Mecánico')
                ->description('Seleccione el empleado que será registrado como mecánico')
                ->icon('heroicon-o-wrench-screwdriver')
                ->schema([
                    Forms\Components\Grid::make(2)->schema([
                        Forms\Components\Select::make('cod_empleado')
                            ->label('Empleado')
                            ->relationship('empleado', 'nombre')
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                $persona = $record->persona;
                                if ($persona) {
                                    $nombre = $persona->razon_social ?: trim($persona->nombres . ' ' . $persona->apellidos);
                                    return "{$record->nombre} - {$nombre} ({$persona->nro_documento})";
                                }
                                return $record->nombre;
                            })
                            ->searchable(['nombre'])
                            ->preload()
                            ->required()
                            ->unique('mecanico', 'cod_empleado', ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Este empleado ya está registrado como mecánico.',
                            ])
                            ->helperText('Seleccione el empleado que ejercerá como mecánico')
                            ->columnSpan(2),
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
                            ->default(fn () => auth()->user()->name ?? 'Sistema')
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
                Tables\Columns\TextColumn::make('cod_mecanico')
                    ->label('#')
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

                Tables\Columns\TextColumn::make('empleado.cargo.descripcion')
                    ->label('Cargo')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('usuario_alta')
                    ->label('Registrado por'),

                Tables\Columns\TextColumn::make('fec_alta')
                    ->label('Fecha Alta')
                    ->dateTime('d/m/Y'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
              //      Tables\Actions\DeleteAction::make(),
                ]),
            ]);
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
            'create' => Pages\CreateMecanico::route('/create'),
            'edit' => Pages\EditMecanico::route('/{record}/edit'),
        ];
    }
}
