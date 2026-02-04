<?php

namespace App\Filament\Resources\NotaResource\Pages;

use App\Filament\Resources\NotaResource;
use App\Models\Nota;
use App\Models\Factura;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateNota extends CreateRecord
{
    protected static string $resource = NotaResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validar que la factura existe y está emitida
        $factura = Factura::find($data['cod_factura']);

        if (!$factura) {
            Notification::make()
                ->title('Error')
                ->body('La factura seleccionada no existe')
                ->danger()
                ->send();
            $this->halt();
        }

        if ($factura->estado !== 'Emitida') {
            Notification::make()
                ->title('Error')
                ->body('Solo se pueden crear notas para facturas emitidas')
                ->danger()
                ->send();
            $this->halt();
        }

        // Validar monto para nota de crédito
        if ($data['tipo_nota'] === 'credito') {
            $saldoActual = $factura->getSaldoConNotas();

            if ($data['monto_total'] > $saldoActual) {
                Notification::make()
                    ->title('Error')
                    ->body('El monto de la nota de crédito (' . number_format($data['monto_total'], 0, ',', '.') . ' Gs) no puede exceder el saldo de la factura (' . number_format($saldoActual, 0, ',', '.') . ' Gs)')
                    ->danger()
                    ->send();
                $this->halt();
            }
        }

        // Validar que hay detalles
        if (!isset($data['detalles']) || empty($data['detalles'])) {
            Notification::make()
                ->title('Error')
                ->body('Debe agregar al menos un ítem en los detalles')
                ->danger()
                ->send();
            $this->halt();
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        return Nota::crearNotaCompleta($data);
    }

    protected function afterCreate(): void
    {
        $nota = $this->record;
        $factura = $nota->factura;

        $tipoLabel = $nota->tipo_nota === 'credito' ? 'Crédito' : 'Débito';
        $efecto = $nota->tipo_nota === 'credito' ? 'reducido' : 'aumentado';

        Notification::make()
            ->title('Nota de ' . $tipoLabel . ' creada')
            ->body(
                'Se ha ' . $efecto . ' el saldo de la factura ' . $factura->numero_factura .
                ' en ' . number_format($nota->monto_total, 0, ',', '.') . ' Gs. ' .
                'Nuevo saldo: ' . number_format($factura->fresh()->getSaldoConNotas(), 0, ',', '.') . ' Gs'
            )
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
