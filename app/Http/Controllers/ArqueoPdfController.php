<?php

namespace App\Http\Controllers;

use App\Models\AperturaCaja;
use Illuminate\Http\Request;

class ArqueoPdfController extends Controller
{
    public function show(AperturaCaja $apertura)
    {
        $apertura->load([
            'caja',
            'sucursal',
            'movimientos.usuarioAlta',
            'cobros.cliente',
        ]);

        $usuario = auth()->user()?->name ?? 'Sistema';
        $gs = fn($val) => number_format((float)$val, 0, ',', '.') . ' Gs.';
        $safe = fn($val, $default = '—') => is_null($val) || $val === '' ? $default : $val;
        $fecha = fn($val) => $val ? \Carbon\Carbon::parse($val)->format('d/m/Y') : '—';
        $hora = fn($val) => $val ? \Carbon\Carbon::parse($val)->format('H:i') : '—';

        $ingresos = $apertura->movimientos->where('tipo_movimiento', 'Ingreso');
        $egresos = $apertura->movimientos->where('tipo_movimiento', 'Egreso');
        $totalIngresos = $ingresos->sum('monto');
        $totalEgresos = $egresos->sum('monto');
        $saldoEsperado = $apertura->monto_inicial + $totalIngresos - $totalEgresos;
        $diferencia = $apertura->diferencia ?? 0;

        // Movimientos table
        $movimientosHtml = '';
        foreach ($apertura->movimientos as $mov) {
            $movimientosHtml .= '<tr>
                <td style="padding:2px 4px;font-size:7pt;border-bottom:1px solid #e5e7eb;">' . $fecha($mov->fecha_movimiento) . '</td>
                <td style="padding:2px 4px;font-size:7pt;border-bottom:1px solid #e5e7eb;">' . $safe($mov->tipo_movimiento) . '</td>
                <td style="padding:2px 4px;font-size:7pt;border-bottom:1px solid #e5e7eb;">' . $safe($mov->concepto) . '</td>
                <td style="padding:2px 4px;font-size:7pt;border-bottom:1px solid #e5e7eb;text-align:right;">' . $gs($mov->monto) . '</td>
                <td style="padding:2px 4px;font-size:7pt;border-bottom:1px solid #e5e7eb;">' . $safe($mov->descripcion) . '</td>
            </tr>';
        }
        if ($apertura->movimientos->isEmpty()) {
            $movimientosHtml = '<tr><td colspan="5" style="padding:4px;font-size:7pt;text-align:center;color:#6b7280;">Sin movimientos</td></tr>';
        }

        // Cobros table
        $cobrosHtml = '';
        foreach ($apertura->cobros as $cobro) {
            $cobrosHtml .= '<tr>
                <td style="padding:2px 4px;font-size:7pt;border-bottom:1px solid #e5e7eb;">' . $cobro->cod_cobro . '</td>
                <td style="padding:2px 4px;font-size:7pt;border-bottom:1px solid #e5e7eb;">' . $fecha($cobro->fecha_cobro) . '</td>
                <td style="padding:2px 4px;font-size:7pt;border-bottom:1px solid #e5e7eb;">' . $safe($cobro->cliente?->nombre_completo ?? $cobro->cliente?->persona?->nombre_completo) . '</td>
                <td style="padding:2px 4px;font-size:7pt;border-bottom:1px solid #e5e7eb;text-align:right;">' . $gs($cobro->monto_total) . '</td>
                <td style="padding:2px 4px;font-size:7pt;border-bottom:1px solid #e5e7eb;">' . $safe($cobro->estado) . '</td>
            </tr>';
        }
        if ($apertura->cobros->isEmpty()) {
            $cobrosHtml = '<tr><td colspan="5" style="padding:4px;font-size:7pt;text-align:center;color:#6b7280;">Sin cobros</td></tr>';
        }

        $diferenciaColor = $diferencia > 0 ? '#059669' : ($diferencia < 0 ? '#dc2626' : '#6b7280');
        $diferenciaTexto = $diferencia > 0 ? 'SOBRANTE' : ($diferencia < 0 ? 'FALTANTE' : 'CUADRE');

        $html = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Arqueo de Caja #' . $apertura->cod_apertura . '</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;font-size:7.5pt;color:#000;line-height:1.2;padding:12px 18px}
.header{text-align:center;border-bottom:1px solid #000;padding-bottom:6px;margin-bottom:8px}
.header h1{font-size:11pt;margin:0}
.header p{font-size:7pt;margin:1px 0 0;color:#000}
.header .nro{font-size:8pt;margin-top:3px;font-weight:bold}
.titulo{font-size:8pt;font-weight:bold;text-transform:uppercase;border-bottom:1px solid #000;margin-top:8px;margin-bottom:3px;padding-bottom:1px}
.datos{width:100%;border-collapse:collapse}
.datos td{padding:1px 3px;vertical-align:top;font-size:7pt}
.datos .lbl{font-weight:bold;width:22%}
.datos .val{width:28%}
.resumen{width:100%;border-collapse:collapse;margin-top:4px}
.resumen td{padding:3px 4px;font-size:8pt;border:1px solid #000}
.resumen .lbl{font-weight:bold;background:#f3f4f6;width:50%}
.resumen .val{text-align:right;font-weight:bold}
.tabla{width:100%;border-collapse:collapse;margin-top:4px}
.tabla th{padding:2px 4px;font-size:7pt;background:#f3f4f6;border:1px solid #000;text-align:left;font-weight:bold}
.tabla td{padding:2px 4px;font-size:7pt;border:1px solid #e5e7eb}
.tabla .total{font-weight:bold;background:#f3f4f6}
.footer{text-align:center;font-size:6pt;margin-top:10px;border-top:1px solid #000;padding-top:3px}
</style>
</head>
<body>

<div class="header">
    <h1>AltoPiston</h1>
    <p>Taller Mecanico</p>
    <div class="nro">ARQUEO DE CAJA #' . str_pad($apertura->cod_apertura, 6, '0', STR_PAD_LEFT) . '</div>
</div>

<div class="titulo">Datos de la Caja</div>
<table class="datos">
    <tr>
        <td class="lbl">Caja:</td><td class="val">' . $safe($apertura->caja?->descripcion) . '</td>
        <td class="lbl">Sucursal:</td><td class="val">' . $safe($apertura->sucursal?->descripcion) . '</td>
    </tr>
    <tr>
        <td class="lbl">Apertura:</td><td class="val">' . $fecha($apertura->fecha_apertura) . ' ' . $hora($apertura->hora_apertura) . '</td>
        <td class="lbl">Cierre:</td><td class="val">' . ($apertura->fecha_cierre ? $fecha($apertura->fecha_cierre) . ' ' . $hora($apertura->hora_cierre) : '—') . '</td>
    </tr>
    <tr>
        <td class="lbl">Usuario:</td><td class="val">' . $safe($apertura->usuario) . '</td>
        <td class="lbl">Estado:</td><td class="val">' . $safe($apertura->estado) . '</td>
    </tr>
</table>

<div class="titulo">Resumen de Caja</div>
<table class="resumen">
    <tr>
        <td class="lbl">Monto Inicial</td>
        <td class="val">' . $gs($apertura->monto_inicial) . '</td>
    </tr>
    <tr>
        <td class="lbl">Total Ingresos</td>
        <td class="val" style="color:#059669;">' . $gs($totalIngresos) . '</td>
    </tr>
    <tr>
        <td class="lbl">Total Egresos</td>
        <td class="val" style="color:#dc2626;">' . $gs($totalEgresos) . '</td>
    </tr>
    <tr>
        <td class="lbl">Saldo Esperado</td>
        <td class="val">' . $gs($saldoEsperado) . '</td>
    </tr>
    <tr>
        <td class="lbl" style="background:#e5e7eb;">DIFERENCIA (' . $diferenciaTexto . ')</td>
        <td class="val" style="background:#e5e7eb;color:' . $diferenciaColor . ';">' . $gs($diferencia) . '</td>
    </tr>
</table>

<div class="titulo">Movimientos de Caja</div>
<table class="tabla">
    <thead>
        <tr>
            <th>Fecha</th>
            <th>Tipo</th>
            <th>Concepto</th>
            <th style="text-align:right">Monto</th>
            <th>Descripcion</th>
        </tr>
    </thead>
    <tbody>' . $movimientosHtml . '</tbody>
</table>

<div class="titulo">Cobros Registrados</div>
<table class="tabla">
    <thead>
        <tr>
            <th>Nro</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th style="text-align:right">Monto</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>' . $cobrosHtml . '</tbody>
</table>

<div class="footer">
    Arqueo generado por ' . $usuario . ' - ' . now()->format('d/m/Y H:i') . '
</div>

</body>
</html>';

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'sans-serif');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="arqueo-caja-' . $apertura->cod_apertura . '.pdf"');
    }
}
