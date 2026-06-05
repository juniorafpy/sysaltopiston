<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaisResource\Pages;
use App\Filament\Resources\PaisResource\RelationManagers;
use App\Models\Pais;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Closure;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaisResource extends Resource
{
    protected static ?string $model = Pais::class;

    protected static ?string $navigationGroup = 'Referenciales/Compras';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-flag';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('descripcion')
                ->label('Descripción')
                ->maxLength(50)
                ->required(),
            Forms\Components\TextInput::make('gentilicio')
                ->maxLength(20)
                ->required(),
            Forms\Components\TextInput::make('abreviatura')
                ->maxLength(3)
                ->length(3)
                ->required(),
            Forms\Components\Toggle::make('estado')
                ->label('Activo')
                ->default(true)
                ->formatStateUsing(fn ($state) => $state !== 'N')
                ->dehydrateStateUsing(fn ($state) => $state ? 'S' : 'N')
                ->inline(false),
            Forms\Components\TextInput::make('usuario_alta')
                ->label('Usuario Alta')
                ->default(fn () => auth()->user()->name)
                ->disabled()
                ->dehydrated(),
            Forms\Components\TextInput::make('fec_alta')
                ->label('Fecha Alta')
                ->default(now())
                ->disabled()
                ->dehydrated()
                ->hidden(fn ($livewire) => $livewire instanceof \Filament\Resources\Pages\CreateRecord),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cod_pais')
                    ->label('Código')
                    ->alignment(Alignment::Center),
                Tables\Columns\TextColumn::make('descripcion')->searchable(),
                Tables\Columns\TextColumn::make('gentilicio')->searchable(),
                Tables\Columns\TextColumn::make('abreviatura')->searchable(),
                Tables\Columns\IconColumn::make('estado')
                    ->label('Activo')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->estado === 'S'),
                Tables\Columns\TextColumn::make('usuario_alta'),
                Tables\Columns\TextColumn::make('fec_alta')->date('d/m/Y'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Editar')
                    ->modal()
                    ->modalSubmitActionLabel('Guardar'),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPais::route('/'),
            // quitamos 'create' para usar solo el modal/slide
            //'edit' => Pages\EditPais::route('/{record}/edit'),
        ];
    }

}
