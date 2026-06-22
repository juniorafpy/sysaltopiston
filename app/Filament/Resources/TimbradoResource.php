<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TimbradoResource\Pages;
use App\Models\Timbrado;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TimbradoResource extends Resource
{
    protected static ?string $model = Timbrado::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Referenciales/Ventas';
    protected static ?string $navigationLabel = 'Timbrados';
    protected static ?string $modelLabel = 'Timbrado';
    protected static ?string $pluralModelLabel = 'Timbrados';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos del Timbrado')
                    ->schema([
                        Forms\Components\TextInput::make('numero_timbrado')
                            ->label('Número Timbrado')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxLength(15)
                            ->extraInputAttributes(['inputmode' => 'numeric'])
                            ->validationMessages([
                                'numeric' => 'El número de timbrado solo debe contener dígitos numéricos.',
                                'min' => 'El número de timbrado debe ser mayor a 0.',
                            ]),

                        Forms\Components\Select::make('tipo_comprobante')
                            ->label('Tipo Comprobante')
                            ->options(function () {
                                return \App\Models\TipoComprobante::all()
                                    ->pluck('descripcion', 'tipo_comprobante');
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('cod_sucursal')
                            ->label('Sucursal')
                            ->relationship('sucursal', 'descripcion')
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $sucursal = \App\Models\Sucursal::find($state);
                                    $set('establecimiento', $sucursal?->establecimiento ?? '');
                                } else {
                                    $set('establecimiento', '');
                                }
                            })
                            ->required(),

                        Forms\Components\DatePicker::make('fecha_inicio_vigencia')
                            ->label('Inicio Vigencia')
                            ->native(true)
                            ->displayFormat('d/m/Y')
                            ->required(),

                        Forms\Components\DatePicker::make('fecha_fin_vigencia')
                            ->label('Fin Vigencia')
                            ->native(true)
                            ->displayFormat('d/m/Y')
                            ->required(),

                        Forms\Components\TextInput::make('numero_inicial')
                            ->label('Número Inicial')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxLength(7)
                            ->extraInputAttributes(['inputmode' => 'numeric'])
                            ->validationMessages([
                                'numeric' => 'Solo se permiten números.',
                                'min' => 'Debe ser mayor a 0.',
                            ]),

                        Forms\Components\TextInput::make('numero_final')
                            ->label('Número Final')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxLength(7)
                            ->extraInputAttributes(['inputmode' => 'numeric'])
                            ->validationMessages([
                                'numeric' => 'Solo se permiten números.',
                                'min' => 'Debe ser mayor a 0.',
                            ]),

                        Forms\Components\Hidden::make('numero_actual')
                            ->default(1)
                            ->dehydrated(),

                        Forms\Components\TextInput::make('establecimiento')
                            ->label('Establecimiento (auto)')
                            ->required()
                            ->disabled()
                            ->dehydrated()
                            ->extraInputAttributes(['inputmode' => 'numeric'])
                            ->helperText('Se carga automáticamente desde la sucursal seleccionada'),

                        Forms\Components\TextInput::make('punto_expedicion')
                            ->label('Punto Expedición (3 dígitos)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(999)
                            ->maxLength(3)
                            ->extraInputAttributes(['inputmode' => 'numeric'])
                            ->validationMessages([
                                'numeric' => 'El punto de expedición solo debe contener dígitos numéricos.',
                                'min' => 'Debe ser entre 1 y 999.',
                                'max' => 'Debe ser entre 1 y 999.',
                            ]),

                        Forms\Components\Toggle::make('activo')
                            ->label('Activo')
                            ->default(true)
                            ->formatStateUsing(fn ($state) => $state === true || $state === 1 || $state === '1')
                            ->dehydrateStateUsing(fn ($state) => $state ? 1 : 0),

                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('usuario_alta')
                                ->label('Usuario de Registro')
                                ->default(fn () => auth()->user()->name)
                                ->disabled()
                                ->dehydrated(false)
                                ->columnSpan(1),

                            Forms\Components\DatePicker::make('fec_alta')
                                ->label('Fecha de Registro')
                                ->default(now())
                                ->disabled()
                                ->dehydrated(false)
                                ->displayFormat('d/m/Y')
                                ->columnSpan(1),
                        ]),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_timbrado')
                    ->label('Timbrado')
                    ->searchable()
                    ->sortable()
                    ->size('sm'),
                Tables\Columns\TextColumn::make('sucursal.descripcion')
                    ->label('Sucursal')
                    ->searchable()
                    ->sortable()
                    ->size('sm'),
                Tables\Columns\TextColumn::make('tipoComprobante.descripcion')
                    ->label('Tipo')
                    ->badge()
                    ->size('sm')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_inicio_vigencia')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->size('sm')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_fin_vigencia')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->size('sm')
                    ->sortable(),
                Tables\Columns\TextColumn::make('numero_actual')
                    ->label('Actual')
                    ->size('sm')
                    ->sortable(),
                Tables\Columns\TextColumn::make('numero_final')
                    ->label('Final')
                    ->size('sm')
                    ->sortable(),
                Tables\Columns\IconColumn::make('activo')
                    ->label('')
                    ->boolean()
                    ->size('sm'),
                Tables\Columns\TextColumn::make('usuario_alta')
                    ->label('Usuario')
                    ->size('sm')
                    ->toggleable(isToggledHiddenByDefault: true)
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
                        $existe = Timbrado::whereRaw('UPPER(TRIM(numero_timbrado)) = ?', [strtoupper(trim($data['numero_timbrado']))])
                            ->where('cod_sucursal', $data['cod_sucursal'])
                            ->where('tipo_comprobante', $data['tipo_comprobante'])
                            ->where('cod_timbrado', '!=', $record->cod_timbrado)
                            ->exists();
                        if ($existe) {
                            $action->getLivewire()->dispatch('swal:error', message: 'El número de timbrado ya está registrado para esta sucursal y tipo de comprobante.');
                            $action->halt();
                        }
                    })
                    ->after(function ($record, $livewire) {
                        $livewire->dispatch('swal:success', message: 'Timbrado actualizado exitosamente.');
                    }),
            ])
            ->defaultSort('cod_timbrado', 'desc')
            ->recordClasses(fn () => 'py-1');
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
            'index' => Pages\ManageTimbrados::route('/'),
        ];
    }
}
