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
                    Forms\Components\TextInput::make('descripcion')->required()->maxLength(50),
                    Forms\Components\TextInput::make('gentilicio')->required()->maxLength(20),
                    Forms\Components\TextInput::make('abreviatura')->required()->maxLength(3),
                ])
                ->using(function (array $data) {
                    $data['usuario_alta'] = auth()->user()?->name ?? 'sistema';
                    $data['fec_alta']     = now();
                    return Pais::create($data);
                })
                ->modalHeading('Registrar país')
                ->modalSubmitActionLabel('Guardar')
                ->modalWidth('lg')
                ->slideOver() // panel lateral (por defecto a la derecha)
                ->successNotificationTitle('País creado'),
        ];
    }
}
