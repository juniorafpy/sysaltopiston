<?php

namespace App\Http\Controllers;

use App\Models\OrdenServicio;
use App\Models\RecepcionVehiculo;
use App\Models\EntregaVehiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteServiciosController extends Controller
{
    public function pdf(Request $request)
    {
        $tab = $request->get('tab', 'ordenes');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $cliente = $request->get('cliente');
        $mecanico = $request->get('mecanico');
        $estado = $request->get('estado');

        $data = [];
        $titulo = '';

        switch ($tab) {
            case 'ordenes':
                $titulo = 'Reporte de Órdenes de Servicio';
                $query = OrdenServicio::query();
                if ($fechaDesde) $query->whereDate('fecha_inicio', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fecha_inicio', '<=', $fechaHasta);
                if ($cliente) $query->where('cod_cliente', $cliente);
                if ($mecanico) $query->where('cod_mecanico', $mecanico);
                if ($estado) $query->where('estado_trabajo', $estado);
                $data = $query->with(['cliente', 'mecanicoAsignado.persona', 'recepcionVehiculo.vehiculo'])
                    ->orderBy('fecha_inicio', 'desc')
                    ->get();
                break;

            case 'recepciones':
                $titulo = 'Reporte de Recepciones de Vehículos';
                $query = RecepcionVehiculo::query();
                if ($fechaDesde) $query->whereDate('fecha_recepcion', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fecha_recepcion', '<=', $fechaHasta);
                if ($cliente) $query->where('cod_cliente', $cliente);
                if ($estado) $query->where('estado', $estado);
                $data = $query->with(['cliente', 'vehiculo'])
                    ->orderBy('fecha_recepcion', 'desc')
                    ->get();
                break;

            case 'entregas':
                $titulo = 'Reporte de Entregas de Vehículos';
                $query = EntregaVehiculo::query();
                if ($fechaDesde) $query->whereDate('fecha_entrega', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fecha_entrega', '<=', $fechaHasta);
                $data = $query->with(['ordenServicio.cliente', 'ordenServicio.recepcionVehiculo.vehiculo'])
                    ->orderBy('fecha_entrega', 'desc')
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

        if ($tab === 'ordenes') {
            $html .= '<th>N°</th><th>Fecha</th><th>Cliente</th><th>Vehículo</th><th>Mecánico</th><th>Estado</th><th>Total</th>';
        } elseif ($tab === 'recepciones') {
            $html .= '<th>N°</th><th>Fecha</th><th>Cliente</th><th>Vehículo</th><th>Motivo</th><th>Estado</th>';
        } else {
            $html .= '<th>N°</th><th>Fecha</th><th>OS</th><th>Cliente</th><th>Vehículo</th><th>Recibe</th><th>Km</th>';
        }

        $html .= '</tr>
    </thead>
    <tbody>';

        foreach ($data as $item) {
            $html .= '<tr>';
            if ($tab === 'ordenes') {
                $html .= '<td>' . $item->id . '</td>';
                $html .= '<td>' . $fecha($item->fecha_inicio) . '</td>';
                $html .= '<td>' . ($item->cliente?->nombre_completo ?? 'N/A') . '</td>';
                $html .= '<td>' . ($item->recepcionVehiculo?->vehiculo?->matricula ?? 'N/A') . '</td>';
                $html .= '<td>' . ($item->mecanicoAsignado?->persona?->nombre_completo ?? 'Sin asignar') . '</td>';
                $html .= '<td>' . $item->estado_trabajo . '</td>';
                $html .= '<td>' . $gs($item->total ?? 0) . '</td>';
            } elseif ($tab === 'recepciones') {
                $html .= '<td>' . $item->id . '</td>';
                $html .= '<td>' . $fecha($item->fecha_recepcion) . '</td>';
                $html .= '<td>' . ($item->cliente?->nombre_completo ?? 'N/A') . '</td>';
                $html .= '<td>' . ($item->vehiculo?->matricula ?? 'N/A') . '</td>';
                $html .= '<td>' . ($item->motivo_ingreso ?? 'N/A') . '</td>';
                $html .= '<td>' . $item->estado . '</td>';
            } else {
                $html .= '<td>' . $item->id . '</td>';
                $html .= '<td>' . $fecha($item->fecha_entrega) . '</td>';
                $html .= '<td>' . ($item->ordenServicio?->id ?? 'N/A') . '</td>';
                $html .= '<td>' . ($item->ordenServicio?->cliente?->nombre_completo ?? 'N/A') . '</td>';
                $html .= '<td>' . ($item->ordenServicio?->recepcionVehiculo?->vehiculo?->matricula ?? 'N/A') . '</td>';
                $html .= '<td>' . ($item->persona_recibe ?? 'N/A') . '</td>';
                $html .= '<td>' . number_format($item->kilometraje_salida ?? 0, 0, ',', '.') . ' km</td>';
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
            ->header('Content-Disposition', 'inline; filename="reporte-servicios-' . $tab . '.pdf"');
    }

    public function excel(Request $request)
    {
        $tab = $request->get('tab', 'ordenes');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');
        $cliente = $request->get('cliente');
        $mecanico = $request->get('mecanico');
        $estado = $request->get('estado');

        $data = [];
        $titulo = '';
        $headers = [];

        switch ($tab) {
            case 'ordenes':
                $titulo = 'Reporte de Órdenes de Servicio';
                $headers = ['N°', 'Fecha', 'Cliente', 'Vehículo', 'Mecánico', 'Estado', 'Total'];
                $query = OrdenServicio::query();
                if ($fechaDesde) $query->whereDate('fecha_inicio', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fecha_inicio', '<=', $fechaHasta);
                if ($cliente) $query->where('cod_cliente', $cliente);
                if ($mecanico) $query->where('cod_mecanico', $mecanico);
                if ($estado) $query->where('estado_trabajo', $estado);
                $data = $query->with(['cliente', 'mecanicoAsignado.persona', 'recepcionVehiculo.vehiculo'])
                    ->orderBy('fecha_inicio', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            $item->id,
                            $item->fecha_inicio ? $item->fecha_inicio->format('d/m/Y') : '',
                            $item->cliente?->nombre_completo ?? 'N/A',
                            $item->recepcionVehiculo?->vehiculo?->matricula ?? 'N/A',
                            $item->mecanicoAsignado?->persona?->nombre_completo ?? 'Sin asignar',
                            $item->estado_trabajo,
                            $item->total ?? 0,
                        ];
                    });
                break;

            case 'recepciones':
                $titulo = 'Reporte de Recepciones de Vehículos';
                $headers = ['N°', 'Fecha', 'Cliente', 'Vehículo', 'Motivo', 'Estado'];
                $query = RecepcionVehiculo::query();
                if ($fechaDesde) $query->whereDate('fecha_recepcion', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fecha_recepcion', '<=', $fechaHasta);
                if ($cliente) $query->where('cod_cliente', $cliente);
                if ($estado) $query->where('estado', $estado);
                $data = $query->with(['cliente', 'vehiculo'])
                    ->orderBy('fecha_recepcion', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            $item->id,
                            $item->fecha_recepcion ? \Carbon\Carbon::parse($item->fecha_recepcion)->format('d/m/Y') : '',
                            $item->cliente?->nombre_completo ?? 'N/A',
                            $item->vehiculo?->matricula ?? 'N/A',
                            $item->motivo_ingreso ?? 'N/A',
                            $item->estado,
                        ];
                    });
                break;

            case 'entregas':
                $titulo = 'Reporte de Entregas de Vehículos';
                $headers = ['N°', 'Fecha', 'OS', 'Cliente', 'Vehículo', 'Recibe', 'Km Salida'];
                $query = EntregaVehiculo::query();
                if ($fechaDesde) $query->whereDate('fecha_entrega', '>=', $fechaDesde);
                if ($fechaHasta) $query->whereDate('fecha_entrega', '<=', $fechaHasta);
                $data = $query->with(['ordenServicio.cliente', 'ordenServicio.recepcionVehiculo.vehiculo'])
                    ->orderBy('fecha_entrega', 'desc')
                    ->get()
                    ->map(function ($item) {
                        return [
                            $item->id,
                            $item->fecha_entrega ? $item->fecha_entrega->format('d/m/Y') : '',
                            $item->ordenServicio?->id ?? 'N/A',
                            $item->ordenServicio?->cliente?->nombre_completo ?? 'N/A',
                            $item->ordenServicio?->recepcionVehiculo?->vehiculo?->matricula ?? 'N/A',
                            $item->persona_recibe ?? 'N/A',
                            $item->kilometraje_salida ?? 0,
                        ];
                    });
                break;
        }

        $filename = 'reporte-servicios-' . $tab . '-' . now()->format('Ymd-His') . '.csv';

        $response = new StreamedResponse(function () use ($headers, $data, $titulo) {
            $handle = fopen('php://output', 'w');
            
            // BOM para UTF-8
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Título
            fputcsv($handle, [$titulo]);
            fputcsv($handle, []);
            
            // Headers
            fputcsv($handle, $headers);
            
            // Data
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
