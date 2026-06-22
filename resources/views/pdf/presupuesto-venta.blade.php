<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Presupuesto de Venta #{{ $presupuesto->id }}</title>
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
.estado{font-size:10pt;font-weight:bold;padding:5px 10px;border:2px solid #000;display:inline-block;margin-top:10px}
</style>
</head>
<body>

<div class="header">
    <h1>AltoPiston</h1>
    <p>Taller Mecánico</p>
    <div class="nro">PRESUPUESTO DE VENTA N° {{ str_pad($presupuesto->id, 6, '0', STR_PAD_LEFT) }}</div>
</div>

<div class="titulo">Datos del Cliente y Vehículo</div>
<table class="datos">
    <tr>
        <td class="lbl">Cliente:</td>
        <td class="val">{{ $presupuesto->cliente?->persona?->razon_social ?: trim(($presupuesto->cliente?->persona?->nombres ?? '') . ' ' . ($presupuesto->cliente?->persona?->apellidos ?? '')) ?: 'Sin cliente' }}</td>
        <td class="lbl">Documento:</td>
        <td class="val">{{ $presupuesto->cliente?->persona?->nro_documento ?: '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Chapa:</td>
        <td class="val">{{ $presupuesto->diagnostico?->recepcionVehiculo?->vehiculo?->matricula ?: '—' }}</td>
        <td class="lbl">Vehículo:</td>
        <td class="val">{{ $presupuesto->diagnostico?->recepcionVehiculo?->vehiculo?->modelo?->descripcion ?: '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Fecha:</td>
        <td class="val">{{ $presupuesto->fecha_presupuesto ? \Carbon\Carbon::parse($presupuesto->fecha_presupuesto)->format('d/m/Y') : '—' }}</td>
        <td class="lbl">Condición:</td>
        <td class="val">{{ $presupuesto->condicion?->descripcion ?: '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Sucursal:</td>
        <td class="val">{{ $presupuesto->sucursal?->descripcion ?: '—' }}</td>
        <td class="lbl">Usuario:</td>
        <td class="val">{{ $presupuesto->usuario_alta ?: '—' }}</td>
    </tr>
</table>

<div class="titulo">Detalle de Artículos</div>
<table class="tabla">
    <thead>
        <tr>
            <th style="width:5%">N°</th>
            <th style="width:35%">Artículo</th>
            <th style="width:10%" class="num">Cant.</th>
            <th style="width:15%" class="num">Precio Unit.</th>
            <th style="width:10%" class="num">Desc.</th>
            <th style="width:10%" class="num">IVA</th>
            <th style="width:15%" class="num">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($presupuesto->detalles as $i => $detalle)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $detalle->articulo?->descripcion ?: $detalle->descripcion ?: 'Sin descripción' }}</td>
            <td class="num">{{ number_format($detalle->cantidad, 0, ',', '.') }}</td>
            <td class="num">Gs. {{ number_format($detalle->precio_unitario, 0, ',', '.') }}</td>
            <td class="num">{{ $detalle->porcentaje_descuento > 0 ? number_format($detalle->porcentaje_descuento, 0, ',', '.') . '%' : '—' }}</td>
            <td class="num">{{ $detalle->porcentaje_impuesto > 0 ? number_format($detalle->porcentaje_impuesto, 0, ',', '.') . '%' : '—' }}</td>
            <td class="num">Gs. {{ number_format($detalle->total, 0, ',', '.') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

<table class="resumen">
    <tr>
        <td class="lbl">Subtotal:</td>
        <td class="val">Gs. {{ number_format($subtotal, 0, ',', '.') }}</td>
    </tr>
    @if($totalDescuento > 0)
    <tr>
        <td class="lbl">Descuento:</td>
        <td class="val" style="color:#dc2626">Gs. {{ number_format($totalDescuento, 0, ',', '.') }}</td>
    </tr>
    @endif
    <tr>
        <td class="lbl">IVA Total:</td>
        <td class="val">Gs. {{ number_format($totalIva, 0, ',', '.') }}</td>
    </tr>
    <tr>
        <td class="lbl" style="background:#e5e7eb;font-size:10pt">TOTAL:</td>
        <td class="val" style="background:#e5e7eb;font-size:10pt">Gs. {{ number_format($total, 0, ',', '.') }}</td>
    </tr>
</table>

<div style="clear:both"></div>

@if($presupuesto->observaciones)
<div class="titulo">Observaciones</div>
<p style="font-size:8pt">{{ $presupuesto->observaciones }}</p>
@endif

<div class="footer">
    <div class="estado">ESTADO: {{ strtoupper($presupuesto->estado) }}</div>
    <br><br>
    Documento generado el {{ now()->format('d/m/Y H:i') }} — AltoPiston Taller Mecánico
</div>

</body>
</html>
