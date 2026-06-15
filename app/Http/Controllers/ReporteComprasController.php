<?php

namespace App\Http\Controllers;

use App\Models\PedidoCabeceras;
use App\Models\OrdenCompraCabecera;
use App\Models\CompraCabecera;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteComprasController extends Controller
{
    public function pdf(Request $request)
    {
        $tab = $request->get('tab', 'pedidos');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $estado = $request->get('estado');
        $proveedor = $request->get('proveedor');
        $numero = $request->get('numero');
        $tipo = $request->get('tipo');

        $data = [];
        $titulo = '';

        switch ($tab) {
            case 'pedidos':
                $titulo = 'Reporte de Pedidos de Compra';
                $query = PedidoCabeceras::query();
                if ($fechaDesde) $query->whereDate('fec_pedido', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fec_pedido', '<=', $fechaHasta);
                if ($estado) $query->where('estado', $estado);
                $data = $query->with(['ped_empleados.persona', 'sucursal_ped', 'detalles'])
                    ->orderBy('fec_pedido', 'desc')
                    ->get();
                break;

            case 'ordenes':
                $titulo = 'Reporte de Órdenes de Compra';
                $query = OrdenCompraCabecera::query();
                if ($fechaDesde) $query->whereDate('fec_orden', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fec_orden', '<=', $fechaHasta);
                if ($proveedor) $query->where('cod_proveedor', $proveedor);
                if ($estado) $query->where('estado', $estado);
                $data = $query->with(['proveedor.personas_pro', 'sucursale', 'ordenCompraDetalles'])
                    ->orderBy('fec_orden', 'desc')
                    ->get();
                break;

            case 'facturas':
                $titulo = 'Reporte de Facturas de Compra';
                $query = CompraCabecera::query();
                if ($fechaDesde) $query->whereDate('fec_comprobante', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fec_comprobante', '<=', $fechaHasta);
                if ($numero) {
                    $query->where(function ($q) use ($numero) {
                        $q->where('nro_comprobante', 'ilike', '%' . $numero . '%')
                          ->orWhere('ser_comprobante', 'ilike', '%' . $numero . '%');
                    });
                }
                if ($tipo) $query->where('tip_comprobante', $tipo);
                $data = $query->with(['proveedor.personas_pro', 'sucursal', 'detalles'])
                    ->orderBy('fec_comprobante', 'desc')
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

        if ($tab === 'pedidos') {
            $html .= '<th>Código</th><th>Fecha</th><th>Empleado</th><th>Sucursal</th><th>Estado</th><th>Items</th>';
        } elseif ($tab === 'ordenes') {
            $html .= '<th>N° Orden</th><th>Fecha</th><th>Proveedor</th><th>Sucursal</th><th>Estado</th><th>Monto</th>';
        } else {
            $html .= '<th>Comprobante</th><th>Fecha</th><th>Proveedor</th><th>Sucursal</th><th>Tipo</th><th>Monto</th>';
        }

        $html .= '</tr>
    </thead>
    <tbody>';

        foreach ($data as $item) {
            $html .= '<tr>';
            if ($tab === 'pedidos') {
                $html .= '<td>' . $item->cod_pedido . '</td>';
                $html .= '<td>' . $fecha($item->fec_pedido) . '</td>';
                $html .= '<td>' . ($item->ped_empleados?->persona?->nombre_completo ?? 'N/A') . '</td>';
                $html .= '<td>' . ($item->sucursal_ped?->descripcion ?? 'N/A') . '</td>';
                $html .= '<td>' . $item->estado . '</td>';
                $html .= '<td>' . $item->detalles->count() . ' items</td>';
            } elseif ($tab === 'ordenes') {
                $html .= '<td>' . $item->nro_orden_compra . '</td>';
                $html .= '<td>' . $fecha($item->fec_orden) . '</td>';
                $html .= '<td>' . ($item->proveedor?->personas_pro?->nombre_completo ?? 'N/A') . '</td>';
                $html .= '<td>' . ($item->sucursale?->descripcion ?? 'N/A') . '</td>';
                $html .= '<td>' . $item->estado . '</td>';
                $html .= '<td>' . $gs($item->monto_general ?? 0) . '</td>';
            } else {
                $html .= '<td>' . $item->tip_comprobante . '-' . $item->ser_comprobante . '-' . $item->nro_comprobante . '</td>';
                $html .= '<td>' . $fecha($item->fec_comprobante) . '</td>';
                $html .= '<td>' . ($item->proveedor?->personas_pro?->nombre_completo ?? 'N/A') . '</td>';
                $html .= '<td>' . ($item->sucursal?->descripcion ?? 'N/A') . '</td>';
                $html .= '<td>' . $item->tip_comprobante . '</td>';
                $html .= '<td>' . $gs($item->monto_general ?? 0) . '</td>';
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
            ->header('Content-Disposition', 'inline; filename="reporte-compras-' . $tab . '.pdf"');
    }

    public function excel(Request $request)
    {
        $tab = $request->get('tab', 'pedidos');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $estado = $request->get('estado');
        $proveedor = $request->get('proveedor');
        $numero = $request->get('numero');
        $tipo = $request->get('tipo');

        $data = [];
        $titulo = '';
        $headers = [];

        switch ($tab) {
            case 'pedidos':
                $titulo = 'Reporte de Pedidos de Compra';
                $headers = ['Código', 'Fecha', 'Empleado', 'Sucursal', 'Estado', 'Items'];
                $query = PedidoCabeceras::query();
                if ($fechaDesde) $query->whereDate('fec_pedido', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fec_pedido', '<=', $fechaHasta);
                if ($estado) $query->where('estado', $estado);
                $data = $query->with(['ped_empleados.persona', 'sucursal_ped', 'detalles'])
                    ->orderBy('fec_pedido', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            $item->cod_pedido,
                            $item->fec_pedido ? $item->fec_pedido->format('d/m/Y') : '',
                            $item->ped_empleados?->persona?->nombre_completo ?? 'N/A',
                            $item->sucursal_ped?->descripcion ?? 'N/A',
                            $item->estado,
                            $item->detalles->count(),
                        ];
                    });
                break;

            case 'ordenes':
                $titulo = 'Reporte de Órdenes de Compra';
                $headers = ['N° Orden', 'Fecha', 'Proveedor', 'Sucursal', 'Estado', 'Monto'];
                $query = OrdenCompraCabecera::query();
                if ($fechaDesde) $query->whereDate('fec_orden', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fec_orden', '<=', $fechaHasta);
                if ($proveedor) $query->where('cod_proveedor', $proveedor);
                if ($estado) $query->where('estado', $estado);
                $data = $query->with(['proveedor.personas_pro', 'sucursale', 'ordenCompraDetalles'])
                    ->orderBy('fec_orden', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            $item->nro_orden_compra,
                            $item->fec_orden ? $item->fec_orden->format('d/m/Y') : '',
                            $item->proveedor?->personas_pro?->nombre_completo ?? 'N/A',
                            $item->sucursale?->descripcion ?? 'N/A',
                            $item->estado,
                            $item->monto_general ?? 0,
                        ];
                    });
                break;

            case 'facturas':
                $titulo = 'Reporte de Facturas de Compra';
                $headers = ['Comprobante', 'Fecha', 'Proveedor', 'Sucursal', 'Tipo', 'Monto'];
                $query = CompraCabecera::query();
                if ($fechaDesde) $query->whereDate('fec_comprobante', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fec_comprobante', '<=', $fechaHasta);
                if ($numero) {
                    $query->where(function ($q) use ($numero) {
                        $q->where('nro_comprobante', 'ilike', '%' . $numero . '%')
                          ->orWhere('ser_comprobante', 'ilike', '%' . $numero . '%');
                    });
                }
                if ($tipo) $query->where('tip_comprobante', $tipo);
                $data = $query->with(['proveedor.personas_pro', 'sucursal', 'detalles'])
                    ->orderBy('fec_comprobante', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            $item->tip_comprobante . '-' . $item->ser_comprobante . '-' . $item->nro_comprobante,
                            $item->fec_comprobante ? $item->fec_comprobante->format('d/m/Y') : '',
                            $item->proveedor?->personas_pro?->nombre_completo ?? 'N/A',
                            $item->sucursal?->descripcion ?? 'N/A',
                            $item->tip_comprobante,
                            $item->monto_general ?? 0,
                        ];
                    });
                break;
        }

        $filename = 'reporte-compras-' . $tab . '-' . now()->format('Ymd-His') . '.csv';

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
