<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModelosResource\Pages;
use App\Filament\Resources\ModelosResource\RelationManagers;
use App\Models\Modelos;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ModelosResource extends Resource
{
    protected static ?string $model = Modelos::class;

    protected static ?string $navigationGroup = 'Referenciales/Compras';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationIcon = 'heroicon-o-flag';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('descripcion')
                    ->maxLength(50)
                    ->required(),
                /*Forms\Components\TextInput::make('cod_marca')
                    ->numeric(),*/
                    Forms\Components\Select::make('cod_marca')
                    ->label('Marca')
                    ->options(function () {
                        return \App\Models\Marcas::pluck('descripcion', 'cod_marca');
                    })
                    ->searchable()
                    ->required(),

                Forms\Components\Toggle::make('estado')
                    ->label('Estado')
                    ->default(true)
                    ->formatStateUsing(fn ($state) => $state === 'A')
                    ->dehydrateStateUsing(fn ($state) => $state ? 'A' : 'I'),
                   
                Forms\Components\Grid::make(2)->schema([
                    Forms\Components\TextInput::make('usuario_alta')
                        ->label('Usuario Alta')
                        ->default(fn () => auth()->user()->name)
                        ->disabled()
                        ->dehydrated(),

                    Forms\Components\DatePicker::make('fec_alta')
                        ->label('Fecha Alta')
                        ->default(now())
                        ->disabled()
                        ->dehydrated()
                        ->displayFormat('d/m/Y'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('cod_modelo') 
                // ->label('Cod_Modelo')
                 ->width('1%')
                 ->alignment('center'), // Agregar la columna para 'cod_pais'

                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable(),
                Tables\Columns\TextColumn::make('desc_marca')
                    ->label('Marca')
                    ->getStateUsing(function ($record) {
                        return $record->marca ? $record->marca->descripcion : 'N/A';
                    })
                    ->extraAttributes(['class' => 'text-left']),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'A' ? 'Activo' : 'Inactivo')
                    ->colors([
                        'success' => 'A',
                        'danger' => 'I',
                    ]),
                    
                Tables\Columns\TextColumn::make('usuario_alta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fec_alta')
                    ->dateTime()
                    ->formatStateUsing(fn($state) => \Carbon\Carbon::parse($state)->format('d/m/Y H:i:s')), 
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modal()
                    ->modalHeading('Editar Modelo')
                    ->modalSubmitActionLabel('Guardar')
                    ->successNotificationTitle(null)
                    ->before(function (array $data, \Filament\Actions\StaticAction $action, $record) {
                        $existe = \App\Models\Modelos::whereRaw('UPPER(TRIM(descripcion)) = ?', [strtoupper(trim($data['descripcion']))])
                            ->where('cod_marca', $data['cod_marca'])
                            ->where('cod_modelo', '!=', $record->cod_modelo)
                            ->exists();
                        if ($existe) {
                            $action->getLivewire()->dispatch('swal:error', message: 'El modelo ya está registrado para esa marca.');
                            $action->halt();
                        }
                    })
                    ->after(function ($record, $livewire) {
                        $livewire->dispatch('swal:success', message: 'Modelo actualizado exitosamente.');
                    }),
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
            'index' => Pages\ListModelos::route('/'),
        ];
    }
}
