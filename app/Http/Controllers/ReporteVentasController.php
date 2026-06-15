<?php

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Cobro;
use App\Models\AperturaCaja;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteVentasController extends Controller
{
    public function pdf(Request $request)
    {
        $tab = $request->get('tab', 'facturas');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $cliente = $request->get('cliente');
        $estado = $request->get('estado');
        $condicion = $request->get('condicion');

        $data = [];
        $titulo = '';

        switch ($tab) {
            case 'facturas':
                $titulo = 'Reporte de Facturas';
                $query = Factura::query();
                if ($fechaDesde) $query->whereDate('fecha_factura', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fecha_factura', '<=', $fechaHasta);
                if ($cliente) $query->where('cod_cliente', $cliente);
                if ($estado) $query->where('estado', $estado);
                if ($condicion) $query->where('condicion_venta', $condicion);
                $data = $query->with(['cliente'])
                    ->orderBy('fecha_factura', 'desc')
                    ->get();
                break;

            case 'cobros':
                $titulo = 'Reporte de Cobros';
                $query = Cobro::query();
                if ($fechaDesde) $query->whereDate('fecha_cobro', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fecha_cobro', '<=', $fechaHasta);
                if ($cliente) $query->where('cod_cliente', $cliente);
                if ($estado) $query->where('estado', $estado);
                $data = $query->with(['cliente'])
                    ->orderBy('fecha_cobro', 'desc')
                    ->get();
                break;

            case 'aperturas':
                $titulo = 'Reporte de Aperturas de Caja';
                $query = AperturaCaja::query();
                if ($fechaDesde) $query->whereDate('fecha_apertura', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fecha_apertura', '<=', $fechaHasta);
                if ($estado) $query->where('estado', $estado);
                $data = $query->with(['caja', 'sucursal'])
                    ->orderBy('fecha_apertura', 'desc')
                    ->get();
                break;
        }

        $gs = fn($val) => number_format((float)$val, 0, ',', '.') . ' Gs.';
        $fecha = fn($val) => $val ? \Carbon\Carbon::parse($val)->format('d/m/Y') : '—';

        $html = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>' . $titulo . '</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;font-size:8pt;color:#000;line-height:1.3;padding:12px 18px}
.header{text-align:center;border-bottom:1px solid #000;padding-bottom:6px;margin-bottom:8px}
.header h1{font-size:12pt;margin:0}
.header p{font-size:7pt;margin:1px 0 0;color:#000}
.titulo{font-size:9pt;font-weight:bold;text-transform:uppercase;border-bottom:1px solid #000;margin-top:8px;margin-bottom:3px;padding-bottom:1px}
.tabla{width:100%;border-collapse:collapse;margin-top:4px}
.tabla th{padding:3px 4px;font-size:7pt;background:#f3f4f6;border:1px solid #000;text-align:left;font-weight:bold}
.tabla td{padding:2px 4px;font-size:7pt;border:1px solid #e5e7eb}
.footer{text-align:center;font-size:6pt;margin-top:10px;border-top:1px solid #000;padding-top:3px}
</style>
</head>
<body>

<div class="header">
    <h1>AltoPiston</h1>
    <p>Taller Mecánico</p>
    <div class="titulo">' . $titulo . '</div>
</div>

<table class="tabla">
    <thead>
        <tr>';

        if ($tab === 'facturas') {
            $html .= '<th>N°</th><th>Fecha</th><th>Cliente</th><th>Condición</th><th>Estado</th><th>Total</th>';
        } elseif ($tab === 'cobros') {
            $html .= '<th>N°</th><th>Fecha</th><th>Cliente</th><th>Estado</th><th>Monto</th>';
        } else {
            $html .= '<th>N°</th><th>Fecha</th><th>Caja</th><th>Usuario</th><th>Estado</th><th>Monto Inicial</th><th>Saldo Esperado</th>';
        }

        $html .= '</tr>
    </thead>
    <tbody>';

        foreach ($data as $item) {
            $html .= '<tr>';
            if ($tab === 'facturas') {
                $html .= '<td>' . ($item->numero_factura ?? $item->cod_factura) . '</td>';
                $html .= '<td>' . $fecha($item->fecha_factura) . '</td>';
                $html .= '<td>' . ($item->cliente?->nombre_completo ?? 'N/A') . '</td>';
                $html .= '<td>' . ($item->condicion_venta ?? 'N/A') . '</td>';
                $html .= '<td>' . $item->estado . '</td>';
                $html .= '<td>' . $gs($item->total_general ?? 0) . '</td>';
            } elseif ($tab === 'cobros') {
                $html .= '<td>' . $item->cod_cobro . '</td>';
                $html .= '<td>' . $fecha($item->fecha_cobro) . '</td>';
                $html .= '<td>' . ($item->cliente?->nombre_completo ?? 'N/A') . '</td>';
                $html .= '<td>' . $item->estado . '</td>';
                $html .= '<td>' . $gs($item->monto_total ?? 0) . '</td>';
            } else {
                $html .= '<td>' . $item->cod_apertura . '</td>';
                $html .= '<td>' . $fecha($item->fecha_apertura) . '</td>';
                $html .= '<td>' . ($item->caja?->descripcion ?? 'N/A') . '</td>';
                $html .= '<td>' . ($item->usuario ?? 'N/A') . '</td>';
                $html .= '<td>' . $item->estado . '</td>';
                $html .= '<td>' . $gs($item->monto_inicial ?? 0) . '</td>';
                $html .= '<td>' . $gs($item->saldo_esperado ?? 0) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody>
</table>

<div class="footer">
    Generado el ' . now()->format('d/m/Y H:i') . ' - Total: ' . $data->count() . ' registros
</div>

</body>
</html>';

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'sans-serif');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="reporte-ventas-' . $tab . '.pdf"');
    }

    public function excel(Request $request)
    {
        $tab = $request->get('tab', 'facturas');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $cliente = $request->get('cliente');
        $estado = $request->get('estado');
        $condicion = $request->get('condicion');

        $data = [];
        $titulo = '';
        $headers = [];

        switch ($tab) {
            case 'facturas':
                $titulo = 'Reporte de Facturas';
                $headers = ['N°', 'Fecha', 'Cliente', 'Condición', 'Estado', 'Total'];
                $query = Factura::query();
                if ($fechaDesde) $query->whereDate('fecha_factura', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fecha_factura', '<=', $fechaHasta);
                if ($cliente) $query->where('cod_cliente', $cliente);
                if ($estado) $query->where('estado', $estado);
                if ($condicion) $query->where('condicion_venta', $condicion);
                $data = $query->with(['cliente'])
                    ->orderBy('fecha_factura', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            $item->numero_factura ?? $item->cod_factura,
                            $item->fecha_factura ? $item->fecha_factura->format('d/m/Y') : '',
                            $item->cliente?->nombre_completo ?? 'N/A',
                            $item->condicion_venta ?? 'N/A',
                            $item->estado,
                            $item->total_general ?? 0,
                        ];
                    });
                break;

            case 'cobros':
                $titulo = 'Reporte de Cobros';
                $headers = ['N°', 'Fecha', 'Cliente', 'Estado', 'Monto'];
                $query = Cobro::query();
                if ($fechaDesde) $query->whereDate('fecha_cobro', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fecha_cobro', '<=', $fechaHasta);
                if ($cliente) $query->where('cod_cliente', $cliente);
                if ($estado) $query->where('estado', $estado);
                $data = $query->with(['cliente'])
                    ->orderBy('fecha_cobro', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            $item->cod_cobro,
                            $item->fecha_cobro ? $item->fecha_cobro->format('d/m/Y') : '',
                            $item->cliente?->nombre_completo ?? 'N/A',
                            $item->estado,
                            $item->monto_total ?? 0,
                        ];
                    });
                break;

            case 'aperturas':
                $titulo = 'Reporte de Aperturas de Caja';
                $headers = ['N°', 'Fecha', 'Caja', 'Usuario', 'Estado', 'Monto Inicial', 'Saldo Esperado'];
                $query = AperturaCaja::query();
                if ($fechaDesde) $query->whereDate('fecha_apertura', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fecha_apertura', '<=', $fechaHasta);
                if ($estado) $query->where('estado', $estado);
                $data = $query->with(['caja', 'sucursal'])
                    ->orderBy('fecha_apertura', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            $item->cod_apertura,
                            $item->fecha_apertura ? $item->fecha_apertura->format('d/m/Y') : '',
                            $item->caja?->descripcion ?? 'N/A',
                            $item->usuario ?? 'N/A',
                            $item->estado,
                            $item->monto_inicial ?? 0,
                            $item->saldo_esperado ?? 0,
                        ];
                    });
                break;
        }

        $filename = 'reporte-ventas-' . $tab . '-' . now()->format('Ymd-His') . '.csv';

        $response = new StreamedResponse(function () use ($headers, $data, $titulo) {
            $handle = fopen('php://output', 'w');
            
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($handle, [$titulo]);
            fputcsv($handle, []);
            
            fputcsv($handle, $headers);
            
            foreach ($data as $row) {
                fputcsv($handle, $row);
            }
            
            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
