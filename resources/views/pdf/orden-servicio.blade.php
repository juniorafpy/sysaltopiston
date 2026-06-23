<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Servicio #{{ $ordenServicio->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 9pt;
            color: #000;
            line-height: 1.3;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 { font-size: 14pt; margin: 0; }
        .header p { font-size: 8pt; margin: 2px 0 0; }
        .header .nro { font-size: 10pt; margin-top: 5px; font-weight: bold; }
        .titulo {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            border-bottom: 1px solid #000;
            margin-top: 12px;
            margin-bottom: 5px;
            padding-bottom: 2px;
        }
        .datos {
            width: 100%;
            border-collapse: collapse;
        }
        .datos td {
            padding: 2px 5px;
            vertical-align: top;
            font-size: 8pt;
        }
        .datos .lbl { font-weight: bold; width: 20%; }
        .datos .val { width: 30%; }
        .tabla {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }
        .tabla th {
            padding: 4px 5px;
            font-size: 8pt;
            border: 1px solid #000;
            text-align: left;
            font-weight: bold;
        }
        .tabla td {
            padding: 3px 5px;
            font-size: 8pt;
            border: 1px solid #ccc;
        }
        .tabla .num { text-align: right; }
        .footer {
            text-align: center;
            font-size: 7pt;
            margin-top: 30px;
            border-top: 1px solid #000;
            padding-top: 5px;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>AltoPiston</h1>
    <p>Taller Mecanico</p>
    <div class="nro">ORDEN DE SERVICIO N° {{ str_pad($ordenServicio->id, 6, '0', STR_PAD_LEFT) }}</div>
</div>

<div class="titulo">Cliente</div>
<table class="datos">
    <tr>
        <td class="lbl">Nombre:</td>
        <td class="val">{{ $ordenServicio->cliente->nombre_completo ?? 'N/A' }}</td>
        <td class="lbl">Telefono:</td>
        <td class="val">{{ $ordenServicio->cliente->telefono ?? '—' }}</td>
    </tr>
</table>

<div class="titulo">Vehiculo</div>
<table class="datos">
    @if($ordenServicio->recepcionVehiculo && $ordenServicio->recepcionVehiculo->vehiculo)
        @php $vehiculo = $ordenServicio->recepcionVehiculo->vehiculo; @endphp
    <tr>
        <td class="lbl">Matricula:</td>
        <td class="val">{{ $vehiculo->matricula ?? '—' }}</td>
        <td class="lbl">Marca:</td>
        <td class="val">{{ $vehiculo->marca->descripcion ?? '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Modelo:</td>
        <td class="val">{{ $vehiculo->modelo->descripcion ?? '—' }}</td>
        <td class="lbl">Anio:</td>
        <td class="val">{{ $vehiculo->anio ?? '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Color:</td>
        <td class="val">{{ $vehiculo->color->descripcion ?? '—' }}</td>
        <td class="lbl">Kilometraje:</td>
        <td class="val">{{ $ordenServicio->recepcionVehiculo->kilometraje ? number_format($ordenServicio->recepcionVehiculo->kilometraje) . ' km' : '—' }}</td>
    </tr>
    @else
    <tr>
        <td colspan="4">No hay informacion del vehiculo disponible</td>
    </tr>
    @endif
</table>

@if($ordenServicio->diagnostico)
<div class="titulo">Diagnostico</div>
<p style="font-size: 8pt; margin-bottom: 10px;">{{ $ordenServicio->diagnostico->diagnostico_mecanico ?? '—' }}</p>
@endif

<div class="titulo">Detalles del Servicio</div>
<table class="datos">
    <tr>
        <td class="lbl">Mecanico:</td>
        <td class="val">{{ $ordenServicio->mecanico->empleado->persona->nombre_completo ?? 'No asignado' }}</td>
        <td class="lbl">Estado:</td>
        <td class="val">{{ $ordenServicio->estado_trabajo ?? '—' }}</td>
    </tr>
    <tr>
        <td class="lbl">Fecha:</td>
        <td class="val">{{ $ordenServicio->fecha_inicio ? \Carbon\Carbon::parse($ordenServicio->fecha_inicio)->format('d/m/Y') : '—' }}</td>
        <td class="lbl"></td>
        <td class="val"></td>
    </tr>
</table>

<div class="titulo">Articulos</div>
<table class="tabla">
    <thead>
        <tr>
            <th style="width:5%">N°</th>
            <th style="width:75%">Articulo</th>
            <th style="width:20%" class="num">Cant.</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ordenServicio->detalles as $index => $detalle)
        <tr>
            <td class="text-center">{{ $index + 1 }}</td>
            <td>{{ $detalle->articulo->descripcion ?? $detalle->descripcion ?? 'Sin descripcion' }}</td>
            <td class="num">{{ number_format($detalle->cantidad, 0) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

@if($ordenServicio->observaciones_tecnicas)
<div class="titulo">Observaciones</div>
<p style="font-size: 8pt; margin-bottom: 10px;">{{ $ordenServicio->observaciones_tecnicas }}</p>
@endif

<div class="footer">
    OS interna — AltoPiston Taller Mecanico
</div>

</body>
</html>
