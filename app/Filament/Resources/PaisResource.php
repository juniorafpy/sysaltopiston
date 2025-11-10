<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaisResource\Pages;
use App\Filament\Resources\PaisResource\RelationManagers;
use App\Models\Pais;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\Alignment;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaisResource extends Resource
{
    protected static ?string $model = Pais::class;

    protected static ?string $navigationGroup = 'Definiciones';
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
            Forms\Components\Hidden::make('usuario_alta')
                ->default(fn () => auth()->user()?->name ?? 'sistema'),
            Forms\Components\Hidden::make('fec_alta')
                ->default(now()),
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
                Tables\Columns\TextColumn::make('usuario_alta'),
                Tables\Columns\TextColumn::make('fec_alta')->date('d/m/Y'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()// editar en lateral
               //Tables\Actions\DeleteAction::make(),}}\Filament\Tables\Actions\EditAction::make()
        ->label('Editar')
        ->slideOver()                 // panel lateral (tu CSS lo manda a la IZQUIERDA)
        ->modalHeading('Editar país')
        ->modalWidth('lg'),
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
