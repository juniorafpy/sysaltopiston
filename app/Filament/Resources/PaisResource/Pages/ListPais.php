<?php

namespace App\Filament\Resources\PaisResource\Pages;

use App\Filament\Resources\PaisResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Models\Pais;
use Filament\Actions\DeleteAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ListPais extends ListRecords
{
    protected static string $resource = PaisResource::class;

     protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make('crearPais')
                ->label('Crear país')
                ->form([
                    Forms\Components\TextInput::make('descripcion')
                        ->label('Descripción')
                        ->required()
                        ->maxLength(50),
                    Forms\Components\TextInput::make('gentilicio')
                        ->label('Gentilicio')
                        ->required()
                        ->maxLength(20),
                    Forms\Components\TextInput::make('abreviatura')
                        ->label('Abreviatura')
                        ->required()
                        ->maxLength(3),
                    Forms\Components\TextInput::make('usuario_alta')
                        ->label('Usuario Alta')
                        ->default(fn () => auth()->user()->name)
                        ->disabled()
                        ->dehydrated(true),
                    Forms\Components\TextInput::make('fec_alta')
                        ->label('Fecha Alta')
                        ->default(fn () => now()->format('d/m/Y H:i'))
                        ->disabled()
                        ->dehydrated(false),
                ])
                ->using(function (array $data) {
                    $data['usuario_alta'] = auth()->user()?->name ?? 'sistema';
                    $data['fec_alta']     = now();
                    return Pais::create($data);
                })
                ->modalHeading('Registrar país')
                ->modalSubmitActionLabel('Guardar')
                ->modalWidth('lg')
                ->slideOver()
                ->successNotificationTitle('País creado')
                ->createAnother(false),
        ];
    }
}
