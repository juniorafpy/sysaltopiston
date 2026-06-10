<?php

namespace App\Http\Controllers;

use App\Models\EntregaVehiculo;
use Illuminate\Http\Request;

class EntregaPdfController extends Controller
{
    public function show(EntregaVehiculo $entrega)
    {
        $entrega->load([
            'ordenServicio.cliente.persona',
            'ordenServicio.recepcionVehiculo.vehiculo.marca',
            'ordenServicio.recepcionVehiculo.vehiculo.modelo',
            'ordenServicio.presupuestoVenta.recepcionVehiculo.vehiculo.marca',
            'ordenServicio.presupuestoVenta.recepcionVehiculo.vehiculo.modelo',
            'ordenServicio.diagnostico.recepcionVehiculo.vehiculo.marca',
            'ordenServicio.diagnostico.recepcionVehiculo.vehiculo.modelo',
            'ordenServicio.presupuestoVenta.diagnostico.recepcionVehiculo.vehiculo.marca',
            'ordenServicio.presupuestoVenta.diagnostico.recepcionVehiculo.vehiculo.modelo',
        ]);

        $usuario = auth()->user()?->name ?? 'Sistema';

        $rv = $entrega->ordenServicio?->recepcionVehiculo
            ?? $entrega->ordenServicio?->presupuestoVenta?->recepcionVehiculo
            ?? $entrega->ordenServicio?->diagnostico?->recepcionVehiculo
            ?? $entrega->ordenServicio?->presupuestoVenta?->diagnostico?->recepcionVehiculo;
        $v = $rv?->vehiculo;

        $safe = fn($val, $default = '—') => is_null($val) || $val === '' ? $default : $val;
        $km = fn($val) => $val ? number_format((float)$val, 0, ',', '.') . ' km' : '—';
        $fecha = fn($val) => $val ? $val->format('d/m/Y H:i') : '—';

        $firmaDigital = '';
        if ($entrega->firma) {
            $firmaDigital = '<div style="text-align:center;margin-bottom:15px;">
                <div style="font-size:8pt;color:#6b7280;margin-bottom:5px;">Firma Digital Registrada</div>
                <img src="' . $entrega->firma . '" style="max-width:280px;max-height:100px;border:1px solid #e5e7eb;border-radius:3px;padding:5px;">
            </div>';
        }

        $html = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Comprobante de Entrega #' . $entrega->id . '</title>
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
.obs{border:1px solid #000;padding:4px;font-size:7pt;min-height:20px;margin-bottom:6px}
.firma-titulo{font-size:8pt;font-weight:bold;text-transform:uppercase;border-bottom:1px solid #000;margin-top:10px;margin-bottom:4px;padding-bottom:1px}
.firma-texto{font-size:6.5pt;text-align:justify;margin-bottom:8px}
.firma-area{width:100%;margin-top:6px}
.firma-col{width:48%;display:inline-block;vertical-align:bottom;text-align:center}
.firma-linea{border-top:1px solid #000;margin:0 15px;padding-top:2px;font-size:7pt;margin-top:35px}
.firma-label{font-size:6pt;text-transform:uppercase;margin-top:1px}
.firma-doc{font-size:6pt;margin-top:1px}
.footer{text-align:center;font-size:6pt;margin-top:10px;border-top:1px solid #000;padding-top:3px}
</style>
</head>
<body>

<div class="header">
    <h1>AltoPiston</h1>
    <p>Taller Mecanico</p>
    <div class="nro">COMPROBANTE DE ENTREGA #' . str_pad($entrega->id, 6, '0', STR_PAD_LEFT) . '</div>
</div>

<div class="titulo">Orden de Servicio</div>
<table class="datos">
    <tr>
        <td class="lbl">OS Nro:</td><td class="val">' . $safe($entrega->ordenServicio?->id) . '</td>
        <td class="lbl">Fecha:</td><td class="val">' . $fecha($entrega->fecha_entrega) . '</td>
    </tr>
    <tr>
        <td class="lbl">Cliente:</td><td class="val">' . $safe($entrega->ordenServicio?->cliente?->nombre_completo) . '</td>
        <td class="lbl">Documento:</td><td class="val">' . $safe($entrega->ordenServicio?->cliente?->persona?->nro_documento) . '</td>
    </tr>
</table>

<div class="titulo">Vehiculo</div>
<table class="datos">
    <tr>
        <td class="lbl">Matricula:</td><td class="val">' . $safe($v?->matricula) . '</td>
        <td class="lbl">Ano:</td><td class="val">' . $safe($v?->anio) . '</td>
    </tr>
    <tr>
        <td class="lbl">Marca:</td><td class="val">' . $safe($v?->marca?->descripcion) . '</td>
        <td class="lbl">Modelo:</td><td class="val">' . $safe($v?->modelo?->descripcion) . '</td>
    </tr>
    <tr>
        <td class="lbl">Km Ingreso:</td><td class="val">' . $km($rv?->kilometraje) . '</td>
        <td class="lbl">Km Salida:</td><td class="val">' . $km($entrega->kilometraje_salida) . '</td>
    </tr>
</table>

<div class="titulo">Recepcion</div>
<table class="datos">
    <tr>
        <td class="lbl">Recibio:</td><td class="val">' . $safe($entrega->persona_recibe) . '</td>
        <td class="lbl">Documento:</td><td class="val">' . $safe($entrega->documento_recibe) . '</td>
    </tr>
    <tr>
        <td class="lbl">Titular:</td><td class="val">' . ($entrega->recibe_titular ? 'Si' : 'No') . '</td>
        <td class="lbl">Registro:</td><td class="val">' . $safe($entrega->usuario_alta) . '</td>
    </tr>
</table>

<div class="titulo">Observaciones</div>
<div class="obs">' . ($entrega->observaciones ? nl2br($safe($entrega->observaciones)) : 'Sin observaciones') . '</div>

' . $firmaDigital . '

<div class="firma-titulo">Declaracion de Conformidad</div>
<div class="firma-texto">
    Yo, ' . $safe($entrega->persona_recibe) . ', declaro haber recibido el vehiculo 
    ' . $safe($v?->marca?->descripcion . ' ' . $v?->modelo?->descripcion) . ' (' . $safe($v?->matricula) . ') 
    en las condiciones detalladas. Verifique el funcionamiento, el kilometraje y el estado general. 
    Quedo conforme con la reparacion y el estado del vehiculo al momento de la entrega.
</div>

<div class="firma-area">
    <div class="firma-col">
        <div class="firma-linea">' . $safe($entrega->persona_recibe, '________________________') . '</div>
        <div class="firma-label">Firma del Cliente / Quien Recibe</div>
        <div class="firma-doc">Doc: ' . $safe($entrega->documento_recibe) . '</div>
    </div>
    <div class="firma-col">
        <div class="firma-linea">' . $usuario . '</div>
        <div class="firma-label">Firma del Tecnico / Entrega</div>
        <div class="firma-doc">' . now()->format('d/m/Y H:i') . '</div>
    </div>
</div>

<div class="footer">
    AltoPiston - Entrega #' . str_pad($entrega->id, 6, '0', STR_PAD_LEFT) . ' - ' . now()->format('d/m/Y H:i') . '
</div>

</body>
</html>';

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'sans-serif');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A5', 'portrait');
        $dompdf->render();

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="comprobante-entrega-' . $entrega->id . '.pdf"');
    }
}
