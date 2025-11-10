<?php

namespace App\Filament\Resources\FacturaResource\Pages;

use App\Filament\Resources\FacturaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFactura extends EditRecord
{
    protected static string $resource = FacturaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                ->visible(fn ($record) => $record->estado === 'Emitida'),
        ];
    }

    /**
     * Mutate form data before filling the form
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Si tiene presupuesto, activar el toggle
        if (!empty($data['presupuesto_venta_id'])) {
            $data['desde_presupuesto'] = true;
        }

        return $data;
    }

    /**
     * Get the redirect URL after editing the record
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
