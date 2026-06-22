<?php

namespace App\Filament\Resources\TimbradoResource\Pages;

use App\Filament\Resources\TimbradoResource;
use App\Models\Timbrado;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTimbrados extends ManageRecords
{
    protected static string $resource = TimbradoResource::class;

    public function getHeading(): string
    {
        return 'Lista Timbrados';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modal()
                ->modalSubmitActionLabel('Guardar')
                ->createAnother(false)
                ->successNotificationTitle(null)
                ->mutateFormDataUsing(function (array $data): array {
                    $max = Timbrado::max('cod_timbrado') ?? 0;
                    $data['cod_timbrado'] = $max + 1;
                    $data['usuario_alta'] = auth()->user()->name ?? 'Sistema';
                    $data['fec_alta'] = now();
                    $data['numero_inicial'] = str_pad((int)$data['numero_inicial'], 7, '0', STR_PAD_LEFT);
                    $data['numero_final'] = str_pad((int)$data['numero_final'], 7, '0', STR_PAD_LEFT);
                    $data['numero_actual'] = $data['numero_inicial'];
                    return $data;
                })
                ->before(function (array $data, \Filament\Actions\StaticAction $action) {
                    $existe = Timbrado::whereRaw('UPPER(TRIM(numero_timbrado)) = ?', [strtoupper(trim($data['numero_timbrado']))])
                        ->where('cod_sucursal', $data['cod_sucursal'])
                        ->where('tipo_comprobante', $data['tipo_comprobante'])
                        ->exists();
                    if ($existe) {
                        $this->dispatch('swal:error', message: 'El número de timbrado ya está registrado para esta sucursal y tipo de comprobante.');
                        $action->halt();
                    }
                })
                ->after(function () {
                    $this->dispatch('swal:success', message: 'Registrado con exito');
                }),
        ];
    }
}
