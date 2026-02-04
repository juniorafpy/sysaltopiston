<?php

namespace App\Filament\Resources\NotaCreditoDebitoCompraResource\Pages;

use App\Filament\Resources\NotaCreditoDebitoCompraResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateNotaCreditoDebitoCompra extends CreateRecord
{
    protected static string $resource = NotaCreditoDebitoCompraResource::class;

    protected static bool $canCreateAnother = false;
}
