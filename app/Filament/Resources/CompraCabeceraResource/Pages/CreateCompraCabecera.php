<?php

namespace App\Filament\Resources\CompraCabeceraResource\Pages;

use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\CompraCabeceraResource;

class CreateCompraCabecera extends CreateRecord
{
    protected static ?string $title = 'Registrar Factura de Compra';
    protected static string $resource = CompraCabeceraResource::class;

    protected function getFormActions(): array
    {
        return [
            Action::make('create')
                ->label('Guardar')
                ->action('create')
                ->keyBindings(['mod+s'])
                ->color('warning')
                ->icon('heroicon-o-check'),

            Action::make('cancel')
                ->label('Cancelar')
                ->url($this->getResource()::getUrl('index'))
                ->color('danger')
                ->icon('heroicon-o-x-mark'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return '✅ Factura de compra registrada exitosamente';
    }

    protected function afterCreate(): void
    {
        // Generar cuotas automáticamente después de crear la factura
        $this->record->generarCuotas();
    }
}

