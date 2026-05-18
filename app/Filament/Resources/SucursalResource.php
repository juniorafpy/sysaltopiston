<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SucursalResource\Pages;
use App\Models\Sucursal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SucursalResource extends Resource
{
    protected static ?string $model = Sucursal::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Referenciales/Compras';
    protected static ?string $navigationLabel = 'Mantener Sucursal';
    protected static ?string $modelLabel = 'Sucursal';
    protected static ?string $pluralModelLabel = 'Sucursales';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Datos de la Sucursal')
                    ->schema([
                        Forms\Components\TextInput::make('descripcion')
                            ->label('Descripción')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true),

                        Forms\Components\Toggle::make('estado')
                            ->label('Estado')
                            ->default(true)
                            ->formatStateUsing(fn ($state) => $state === 'A')
                            ->dehydrateStateUsing(fn ($state) => $state ? 'A' : 'I'),
                            //->helperText('Activar para estado activo (A), desactivar para inactivo (I)'),
                    ]),

               
                    
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_sucursal')
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
                    ->modalSubmitActionLabel('Guardar'),
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
            'index' => Pages\ListSucursals::route('/'),
        ];
    }
}
