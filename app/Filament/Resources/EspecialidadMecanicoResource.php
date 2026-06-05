<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EspecialidadMecanicoResource\Pages;
use App\Models\EspecialidadMecanico;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EspecialidadMecanicoResource extends Resource
{
    protected static ?string $model = EspecialidadMecanico::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench';
    protected static ?string $navigationGroup = 'Referenciales/Servicios';
    protected static ?string $navigationLabel = 'Mantener Especialidad Mecanico';
    protected static ?string $modelLabel = 'Especialidad';
    protected static ?string $pluralModelLabel = 'Especialidades';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la Especialidad')
                    ->schema([
                        Forms\Components\TextInput::make('descripcion')
                            ->label('Descripción')
                            ->required()
                            ->maxLength(100)
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                            ->afterStateUpdated(fn ($state, callable $set) => $set('descripcion', strtoupper($state))),

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
                Tables\Columns\TextColumn::make('cod_especialidad')
                    ->label('Código')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'A' ? 'Activo' : 'Inactivo')
                    ->colors([
                        'success' => 'A',
                        'danger' => 'I',
                    ]),
                Tables\Columns\TextColumn::make('usuario_alta')
                    ->label('Usuario Alta')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('fec_alta')
                    ->label('Fecha Alta')
                    ->dateTime()
                    ->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->format('d/m/Y'))
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        $existe = EspecialidadMecanico::whereRaw('UPPER(TRIM(descripcion)) = ?', [strtoupper(trim($data['descripcion']))])
                            ->where('cod_especialidad', '!=', $record->cod_especialidad)
                            ->exists();
                        if ($existe) {
                            $action->getLivewire()->dispatch('swal:error', message: 'La especialidad ya está registrada.');
                            $action->halt();
                        }
                    })
                    ->after(function ($record, $livewire) {
                        $livewire->dispatch('swal:success', message: 'Especialidad actualizada exitosamente.');
                    }),
            ])
            ->defaultSort('descripcion', 'asc');
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
            'index' => Pages\ListEspecialidadMecanicos::route('/'),
        ];
    }
}
