<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Recibo de Cobro N° {{ $cobro->numero_recibo ?: str_pad($cobro->cod_cobro, 6, '0', STR_PAD_LEFT) }}</title>
<style>
body{font-family:DejaVu Sans,Arial,sans-serif;font-size:9pt;color:#000;line-height:1.3;padding:20px}
.header{text-align:center;border-bottom:2px solid #000;padding-bottom:10px;margin-bottom:15px}
.header h1{font-size:14pt;margin:0}
.header p{font-size:8pt;margin:2px 0 0;color:#333}
.header .nro{font-size:10pt;margin-top:5px;font-weight:bold}
.titulo{font-size:9pt;font-weight:bold;text-transform:uppercase;border-bottom:1px solid #000;margin-top:12px;margin-bottom:5px;padding-bottom:2px}
.datos{width:100%;border-collapse:collapse}
.datos td{padding:2px 5px;vertical-align:top;font-size:8pt}
.datos .lbl{font-weight:bold;width:20%}
.datos .val{width:30%}
.tabla{width:100%;border-collapse:collapse;margin-top:5px}
.tabla th{padding:4px 5px;font-size:8pt;background:#f0f0f0;border:1px solid #000;text-align:left;font-weight:bold}
.tabla td{padding:3px 5px;font-size:8pt;border:1px solid #ccc}
.tabla .num{text-align:right}
.resumen{width:100%;border-collapse:collapse;margin-top:10px;max-width:300px;float:right}
.resumen td{padding:4px 5px;font-size:9pt;border:1px solid #000}
.resumen .lbl{font-weight:bold;background:#f0f0f0;text-align:right;width:50%}
.resumen .val{text-align:right;font-weight:bold}
.footer{text-align:center;font-size:7pt;margin-top:30px;border-top:1px solid #000;padding-top:5px;clear:both}
.firma{margin-top:50px;text-align:center;font-size:8pt}
.firma-line{border-top:1px solid #000;width:200px;margin:0 auto;padding-top:5px}
</style>
</head>
<body>

<div class="header">
    <h1>AltoPiston</h1>
    <p>Taller Mecánico</p>
    <div class="nro">RECIBO DE COBRO N° {{ $cobro->numero_recibo ?: str_pad($cobro->cod_cobro, 6, '0', STR_PAD_LEFT) }}</div>
</div>

<div class="titulo">Datos del Cobro</div>
<table class="datos">
    <tr>
        <td class="lbl">Fecha:</td>
        <td class="val">{{ $cobro->fecha_cobro ? \Carbon\Carbon::parse($cobro->fecha_cobro)->format('d/m/Y H:i') : '—' }}</td>
        <td class="lbl">Cliente:</td>
        <td class="val">{{ $cobro->cliente?->razon_social ?: trim(($cobro->cliente?->nombres ?? '') . ' ' . ($cobro->cliente?->apellidos ?? '')) ?: 'Sin cliente' }}</td>
    </tr>
    <tr>
        <td class="lbl">Documento:</td>
        <td class="val">{{ $cobro->cliente?->nro_documento ?: '—' }}</td>
        <td class="lbl">Usuario:</td>
        <td class="val">{{ $cobro->usuario_alta ?: '—' }}</td>
    </tr>
</table>

<div class="titulo">Facturas Cobradas</div>
<table class="tabla">
    <thead>
        <tr>
            <th style="width:15%">Factura</th>
            <th style="width:15%">N° Cuota</th>
            <th style="width:40%">Condición</th>
            <th style="width:30%" class="num">Monto Cobrado</th>
        </tr>
    </thead>
    <tbody>
        @foreach($cobro->detalles as $detalle)
        <tr>
            <td>{{ $detalle->factura?->numero_factura ?: '—' }}</td>
            <td>{{ $detalle->numero_cuota ?: '—' }}</td>
            <td>{{ $detalle->factura?->condicion_venta ?: '—' }}</td>
            <td class="num">Gs. {{ number_format($detalle->monto_cuota, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="titulo">Formas de Pago</div>
<table class="tabla">
    <thead>
        <tr>
            <th style="width:30%">Forma de Pago</th>
            <th style="width:25%">Banco</th>
            <th style="width:20%">Tarjeta</th>
            <th style="width:25%" class="num">Monto</th>
        </tr>
    </thead>
    <tbody>
        @foreach($cobro->formasPago as $fp)
        <tr>
            <td>{{ $fp->formaCobro?->descripcion ?: $fp->getTipoTransaccionLabel() }}</td>
            <td>{{ $fp->entidadBancaria?->nombre ?: '—' }}</td>
            <td>{{ $fp->tipoTarjeta?->descripcion ?: '—' }}</td>
            <td class="num">Gs. {{ number_format($fp->monto, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<table class="resumen">
    <tr>
        <td class="lbl" style="background:#e5e7eb;font-size:10pt">TOTAL COBRADO:</td>
        <td class="val" style="background:#e5e7eb;font-size:10pt">Gs. {{ number_format($cobro->monto_total, 0, ',', '.') }}</td>
    </tr>
</table>

<div style="clear:both"></div>

<div class="firma">
    <div class="firma-line">Firma y aclaración del cliente</div>
</div>

<div class="footer">
    Documento generado el {{ now()->format('d/m/Y H:i') }} — AltoPiston Taller Mecánico
</div>

</body>
</html>
