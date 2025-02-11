<?php

namespace App\Filament\Resources\PedidoCabeceraResource\Pages;

use App\Filament\Resources\PedidoCabeceraResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPedidoCabecera extends EditRecord
{
    protected static string $resource = PedidoCabeceraResource::class;

    protected  static bool $canCreateAnother =  false;


}
