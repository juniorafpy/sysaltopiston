<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Models\FacturaVencimiento;
use Illuminate\Http\JsonResponse;

class FacturasPendientesController extends Controller
{
    public function index($codCliente): JsonResponse
    {
        $cuotas = FacturaVencimiento::whereHas('factura', function ($q) use ($codCliente) {
            $q->where('cod_cliente', $codCliente)
                ->where('condicion_venta', 'Crédito')
                ->where('estado', 'Emitida');
        })
            ->where('saldo_pendiente', '>', 0)
            ->with('factura')
            ->get()
            ->map(function ($v) {
                return [
                    'cod_factura' => $v->cod_factura,
                    'numero_cuota' => $v->nro_cuota,
                    'numero_factura' => $v->factura->numero_factura,
                    'fecha_emision' => $v->factura->fecha_factura->format('d/m/Y'),
                    'fecha_vencimiento' => $v->fecha_vencimiento->format('d/m/Y'),
                    'monto_cuota' => (float) $v->monto_cuota,
                    'monto_pagado' => (float) $v->monto_pagado,
                    'saldo_pendiente' => (float) $v->saldo_pendiente,
                ];
            })
            ->values()
            ->toArray();

        return response()->json($cuotas);
    }
}
