<?php

namespace App\Filament\Resources\NotaCreditoDebitoCompraResource\Pages;

use App\Filament\Resources\NotaCreditoDebitoCompraResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateNotaCreditoDebitoCompra extends CreateRecord
{
    protected static string $resource = NotaCreditoDebitoCompraResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function afterCreate(): void
    {
        // Procesar los efectos de la nota (stock y saldo)
        $this->record->procesarEfectos();

        Notification::make()
            ->success()
            ->title('Nota procesada')
            ->body('Los efectos de la nota se han aplicado correctamente.')
            ->send();
    }
}
