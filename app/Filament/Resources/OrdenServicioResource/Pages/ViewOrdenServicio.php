<?php

namespace App\Filament\Resources\OrdenServicioResource\Pages;

use App\Filament\Resources\OrdenServicioResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewOrdenServicio extends ViewRecord
{
    protected static string $resource = OrdenServicioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\Action::make('imprimir_pdf')
                ->label('Imprimir PDF')
                ->icon('heroicon-o-printer')
                ->color('info')
                ->action(fn () => $this->record->generarPDF('download')),

            Actions\Action::make('ver_pdf')
                ->label('Ver PDF')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn () => route('orden-servicio.pdf', $this->record))
                ->openUrlInNewTab(),
        ];
    }
}
