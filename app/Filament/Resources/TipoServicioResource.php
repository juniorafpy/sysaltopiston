<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TipoServicioResource\Pages;
use App\Models\TipoServicio;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TipoServicioResource extends Resource
{
    protected static ?string $model = TipoServicio::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Referenciales/Servicios';
    protected static ?string $navigationLabel = 'Mantener Tipo Servicio';
    protected static ?string $modelLabel = 'Tipo Servicio';
    protected static ?string $pluralModelLabel = 'Tipos de Servicio';
    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del Tipo de Servicio')
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
                Tables\Columns\TextColumn::make('cod_tipo_servicio')
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
                    ->before(function (array $data, \Filament\Actions\StaticAction $action, $record) {
                        $existe = TipoServicio::whereRaw('UPPER(TRIM(descripcion)) = ?', [strtoupper(trim($data['descripcion']))])
                            ->where('cod_tipo_servicio', '!=', $record->cod_tipo_servicio)
                            ->exists();
                        if ($existe) {
                            $action->getLivewire()->dispatch('swal:error', message: 'El tipo de servicio ya está registrado.');
                            $action->halt();
                        }
                    })
                    ->after(function ($record, $livewire) {
                        $livewire->dispatch('swal:success', message: 'Tipo de servicio actualizado exitosamente.');
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
            'index' => Pages\ListTipoServicios::route('/'),
        ];
    }
}
