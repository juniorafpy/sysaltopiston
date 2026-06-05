<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use Illuminate\Http\JsonResponse;

class FacturasPendientesController extends Controller
{
    public function index($codCliente): JsonResponse
    {
        $facturas = Factura::where('cod_cliente', $codCliente)
            ->where('condicion_venta', 'Crédito')
            ->where('estado', 'Emitida')
            ->get()
            ->filter(fn ($f) => $f->getSaldoConNotas() > 0)
            ->map(function ($f) {
                return [
                    'cod_factura' => $f->cod_factura,
                    'numero_factura' => $f->numero_factura,
                    'fecha_emision' => $f->fecha_emision->format('d/m/Y'),
                    'monto_total' => $f->monto_total,
                    'saldo' => $f->getSaldoConNotas(),
                ];
            })
            ->values()
            ->toArray();

        return response()->json($facturas);
    }
}
