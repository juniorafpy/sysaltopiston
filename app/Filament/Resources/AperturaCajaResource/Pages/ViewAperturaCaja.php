<?php

namespace App\Filament\Resources\AperturaCajaResource\Pages;

use App\Filament\Resources\AperturaCajaResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAperturaCaja extends ViewRecord
{
    protected static string $resource = AperturaCajaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Cerrar Caja')
                ->icon('heroicon-o-lock-closed')
                ->color('danger')
                ->visible(fn () => $this->record->estado === 'Abierta'),
            Actions\Action::make('imprimir_arqueo')
                ->label('Imprimir Arqueo')
                ->icon('heroicon-o-printer')
                ->color('primary')
                ->url(fn () => route('apertura-caja.pdf', $this->record))
                ->openUrlInNewTab()
                ->visible(fn () => true),
        ];
    }
}
