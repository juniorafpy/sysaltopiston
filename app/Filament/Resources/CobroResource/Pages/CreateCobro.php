<?php

namespace App\Filament\Resources\CobroResource\Pages;

use App\Filament\Resources\CobroResource;
use App\Models\AperturaCaja;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCobro extends CreateRecord
{
    protected static string $resource = CobroResource::class;

    protected static bool $canCreateAnother = false;

    public function mount(): void
    {
        $user = Auth::user();

        $apertura = AperturaCaja::where('usuario', $user->name)
            ->where('estado', 'Abierta')
            ->first();

        if (!$apertura) {
            Notification::make()
                ->danger()
                ->title('Caja Cerrada')
                ->body('No tienes una caja abierta. Debes abrir caja antes de registrar un cobro.')
                ->persistent()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $apertura = AperturaCaja::where('usuario', $user->name)
            ->where('estado', 'Abierta')
            ->first();

        if (!$apertura) {
            $this->redirect($this->getResource()::getUrl('index'));
        }

        $total = collect($data['detalles'] ?? [])->sum('monto_cuota');
        $totalPagado = collect($data['formas_pago'] ?? [])->sum('monto');

        if (abs($total - $totalPagado) > 1) {
            Notification::make()
                ->warning()
                ->title('Diferencia de montos')
                ->body('El total a cobrar no coincide con el total recibido. Verifique los montos.')
                ->persistent()
                ->send();
            $this->halt();
        }

        $data['cod_apertura'] = $apertura->cod_apertura;
        $data['monto_total'] = $total;
        $data['usuario_alta'] = $user->name;
        $data['fecha_alta'] = now();

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Cobro registrado')
            ->body('El cobro se ha realizado exitosamente.')
            ->duration(5000);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
