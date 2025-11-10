<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico #{{ $diagnostico->id }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
        h1, h2, h3 { margin: 0 0 8px 0; }
        .section { margin-bottom: 16px; }
        .grid { width: 100%; border-collapse: collapse; }
        .grid th, .grid td { padding: 6px 8px; border: 1px solid #ccc; text-align: left; vertical-align: top; }
        .muted { color: #555; }
    </style>
    </head>
<body>
    <h1>Diagnóstico de Vehículo</h1>
    <p class="muted">N° {{ $diagnostico->id }} — Fecha: {{ optional($diagnostico->fecha_diagnostico)->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</p>

    <div class="section">
        <h3>Datos de la Recepción</h3>
        @php($rec = $diagnostico->recepcionVehiculo)
        <table class="grid">
            <tr>
                <th>Cliente</th>
                <td>{{ $rec?->cliente?->nombres ?? '-' }}</td>
                <th>Chapa</th>
                <td>{{ $rec?->vehiculo?->matricula ?? '-' }}</td>
            </tr>
            <tr>
                <th>Marca</th>
                <td>{{ $rec?->vehiculo?->marca?->descripcion ?? '-' }}</td>
                <th>Modelo</th>
                <td>{{ $rec?->vehiculo?->modelo?->descripcion ?? '-' }}</td>
            </tr>
            <tr>
                <th>Motivo de ingreso</th>
                <td colspan="3">{{ $rec?->motivo_ingreso ?? '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h3>Diagnóstico del mecánico</h3>
        <p>{{ $diagnostico->diagnostico_mecanico }}</p>
    </div>

    @if(!empty($diagnostico->observaciones))
    <div class="section">
        <h3>Observaciones</h3>
        <p>{{ $diagnostico->observaciones }}</p>
    </div>
    @endif

    <div class="section">
        <table class="grid">
            <tr>
                <th>Estado</th>
                <td>{{ $diagnostico->estado }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
