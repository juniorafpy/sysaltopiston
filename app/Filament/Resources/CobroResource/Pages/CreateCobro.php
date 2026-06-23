<?php

namespace App\Filament\Resources\CobroResource\Pages;

use App\Filament\Resources\CobroResource;
use App\Models\AperturaCaja;
use App\Models\Cobro;
use App\Models\Timbrado;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCobro extends CreateRecord
{
    protected static string $resource = CobroResource::class;

    protected static bool $canCreateAnother = false;

    protected function getCreateFormAction(): \Filament\Actions\Action
    {
        return parent::getCreateFormAction()->label('Guardar');
    }

    public function mount(): void
    {
        parent::mount();

        $user = Auth::user();
        $apertura = AperturaCaja::where('usuario', $user->name)
            ->where('estado', 'Abierta')
            ->first();

        if (!$apertura) {
            session()->flash('swal-caja-cerrada', 'No tienes una caja abierta. Debes abrir caja antes de registrar un cobro.');
            $this->redirect($this->getResource()::getUrl('index'));
        }

        $codFactura = request()->query('cod_factura');
        if ($codFactura) {
            $factura = \App\Models\Factura::find($codFactura);
            if ($factura) {
                $vencimientos = $factura->vencimientos()
                    ->where('saldo_pendiente', '>', 0)
                    ->get();

                $detalles = $vencimientos->map(function ($v) {
                    return [
                        'cod_factura' => $v->cod_factura,
                        'numero_cuota' => $v->nro_cuota,
                        'monto_cuota' => (float) $v->saldo_pendiente,
                    ];
                })->values()->toArray();

                $this->form->fill([
                    'cod_cliente' => $factura->cod_cliente,
                    'fecha_cobro' => now()->format('Y-m-d H:i:s'),
                    'detalles' => $detalles,
                ]);
            }
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();
        $apertura = AperturaCaja::where('usuario', $user->name)
            ->where('estado', 'Abierta')
            ->first();

        if (!$apertura) {
            session()->flash('swal-caja-cerrada', 'No tienes una caja abierta. Debes abrir caja antes de registrar un cobro.');
            $this->redirect($this->getResource()::getUrl('index'));
        }

        $total = collect($data['detalles'] ?? [])->sum(fn ($i) => (float) ($i['monto_cuota'] ?? 0));
        $totalPagado = collect($data['formas_pago'] ?? [])->sum(fn ($i) => (float) ($i['monto'] ?? 0));

        // Validar que no se repita la misma forma de pago con la misma tarjeta
        $vistos = [];
        foreach ($data['formas_pago'] ?? [] as $fp) {
            $forma = $fp['cod_forma_cobro'] ?? null;
            $tarjeta = $fp['cod_tipo_tarjeta'] ?? null;

            if (blank($forma)) {
                continue;
            }

            $clave = $forma . '-' . ($tarjeta ?: '0');

            if (in_array($clave, $vistos)) {
                Notification::make()
                    ->danger()
                    ->title('Forma de pago duplicada')
                    ->body('No puede repetir la misma forma de pago con la misma tarjeta.')
                    ->persistent()
                    ->send();
                $this->halt();
            }

            $vistos[] = $clave;
        }

        // Validar que no se salteen cuotas
        $cuotasPorFactura = [];
        foreach ($data['detalles'] ?? [] as $d) {
            $cuotasPorFactura[$d['cod_factura']][] = $d['numero_cuota'];
        }
        foreach ($cuotasPorFactura as $codFactura => $cuotas) {
            $minCuota = min($cuotas);
            if ($minCuota > 1) {
                $cuotasPrevias = \App\Models\FacturaVencimiento::where('cod_factura', $codFactura)
                    ->where('nro_cuota', '<', $minCuota)
                    ->where('saldo_pendiente', '>', 0)
                    ->exists();
                if ($cuotasPrevias) {
                    $factura = \App\Models\Factura::find($codFactura);
                    Notification::make()
                        ->warning()
                        ->title('Cuotas anteriores pendientes')
                        ->body("Debe cobrar primero la(s) cuota(s) anterior(es) de la Factura {$factura->numero_factura} antes de la cuota N° {$minCuota}.")
                        ->persistent()
                        ->send();
                    $this->halt();
                }
            }
        }

        if ($totalPagado < $total - 1) {
            Notification::make()
                ->warning()
                ->title('Monto insuficiente')
                ->body('El total recibido (Gs. ' . number_format($totalPagado, 0, ',', '.') . ') es menor al total a cobrar (Gs. ' . number_format($total, 0, ',', '.') . '). Verifique los montos.')
                ->persistent()
                ->send();
            $this->halt();
        }

        if ($totalPagado > $total + 1) {
            $vuelto = $totalPagado - $total;
            Notification::make()
                ->info()
                ->title('Vuelto: Gs. ' . number_format($vuelto, 0, ',', '.'))
                ->body('El cliente pagó Gs. ' . number_format($totalPagado, 0, ',', '.') . ', el vuelto es de Gs. ' . number_format($vuelto, 0, ',', '.') . '.')
                ->duration(7000)
                ->send();
        }

        $data['cod_apertura'] = $apertura->cod_apertura;
        $data['monto_total'] = $total;
        $data['fecha_alta'] = now();

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        $notification = Notification::make()
            ->success()
            ->title('Cobro registrado')
            ->body('El cobro se ha realizado exitosamente.')
            ->duration(7000);

        if ($this->record && $this->record->esPagoCredito()) {
            $notification
                ->persistent()
                ->body('El cobro se ha realizado exitosamente. Imprima el recibo para el cliente.')
                ->actions([
                    Action::make('imprimir_recibo')
                        ->label('Imprimir recibo')
                        ->button()
                        ->url(fn () => route('cobros.recibo.pdf', $this->record))
                        ->openUrlInNewTab(),
                ]);
        }

        return $notification;
    }

    protected function afterCreate(): void
    {
        if ($this->record && $this->record->esPagoCredito() && $this->record->numero_recibo) {
            $this->dispatch('open-pdf', [
                'url' => route('cobros.recibo.pdf', $this->record),
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $mapaFormas = [1 => 'efectivo', 2 => 'tarjeta_credito', 3 => 'tarjeta_debito', 4 => 'transferencia'];
        foreach ($data['formas_pago'] ?? [] as $i => $fp) {
            if (isset($fp['cod_forma_cobro']) && !isset($fp['tipo_transaccion'])) {
                $data['formas_pago'][$i]['tipo_transaccion'] = $mapaFormas[$fp['cod_forma_cobro']] ?? null;
            }
        }

        // Determinar si es pago de factura de crédito y buscar timbrado REC
        $esCredito = false;
        foreach ($data['detalles'] ?? [] as $detalle) {
            $factura = \App\Models\Factura::find($detalle['cod_factura'] ?? null);
            if ($factura && $factura->condicion_venta === 'Crédito') {
                $esCredito = true;
                break;
            }
        }

        $timbradoRecibo = null;
        if ($esCredito) {
            $timbradoRecibo = Timbrado::obtenerTimbradoActivo('3');
            if (!$timbradoRecibo) {
                Notification::make()
                    ->warning()
                    ->title('Sin timbrado de recibos')
                    ->body('No se encontró un timbrado activo de tipo REC (3). El cobro se registrará sin número de recibo.')
                    ->persistent()
                    ->send();
            }
        }

        return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $esCredito, $timbradoRecibo) {
            // Asignar número de recibo si es pago de crédito
            if ($esCredito && $timbradoRecibo) {
                $data['cod_timbrado_recibo'] = $timbradoRecibo->cod_timbrado;
                $data['numero_recibo'] = $timbradoRecibo->obtenerSiguienteNumeroRecibo();
                $timbradoRecibo->incrementarNumeroActualRecibo();
            }

            $cobro = new Cobro();
            $cobro->cod_cliente = $data['cod_cliente'];
            $cobro->cod_apertura = $data['cod_apertura'];
            $cobro->fecha_cobro = $data['fecha_cobro'];
            $cobro->monto_total = $data['monto_total'];
            $cobro->cod_timbrado_recibo = $data['cod_timbrado_recibo'] ?? null;
            $cobro->numero_recibo = $data['numero_recibo'] ?? null;
            $cobro->usuario_alta = Auth::user()->name;
            $cobro->fecha_alta = $data['fecha_alta'];
            $cobro->saveQuietly();

            foreach ($data['detalles'] ?? [] as $detalle) {
                \App\Models\CobroDetalle::create([
                    'cod_cobro' => $cobro->cod_cobro,
                    'cod_factura' => $detalle['cod_factura'],
                    'numero_cuota' => $detalle['numero_cuota'] ?? 1,
                    'monto_cuota' => $detalle['monto_cuota'],
                ]);
            }

            foreach ($data['formas_pago'] ?? [] as $formaPago) {
                \App\Models\CobroFormaPago::create([
                    'cod_cobro' => $cobro->cod_cobro,
                    'tipo_transaccion' => $formaPago['tipo_transaccion'],
                    'monto' => $formaPago['monto'],
                    'cod_forma_cobro' => $formaPago['cod_forma_cobro'] ?? null,
                    'cod_entidad_bancaria' => $formaPago['cod_entidad_bancaria'] ?? null,
                    'cod_tipo_tarjeta' => $formaPago['cod_tipo_tarjeta'] ?? null,
                    'cod_procesadora' => $formaPago['cod_procesadora'] ?? null,
                    'numero_voucher' => $formaPago['numero_voucher'] ?? null,
                    'numero_cheque' => $formaPago['numero_cheque'] ?? null,
                ]);
            }

            return $cobro->fresh(['detalles', 'formasPago', 'cliente']);
        });
    }
}
