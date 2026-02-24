<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Pedidos de Compra</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #111827;
            margin: 20px;
        }

        h1 {
            margin: 0 0 6px;
            font-size: 18px;
        }

        .meta {
            margin-bottom: 14px;
            color: #374151;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 7px;
            text-align: left;
        }

        th {
            background: #f3f4f6;
        }
    </style>
</head>
<body>
    <h1>Reporte de Pedidos de Compra</h1>

    <div class="meta">
        Generado: {{ $fechaGeneracion->format('d/m/Y H:i') }}<br>
        Fecha desde: {{ $fechaDesde ? \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') : 'Todas' }}<br>
        Fecha hasta: {{ $fechaHasta ? \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') : 'Todas' }}<br>
        Estado: {{ $estado && $estado !== 'TODOS' ? $estado : 'Todos' }}<br>
        Total registros: {{ $pedidos->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>N° Pedido</th>
                <th>Fecha</th>
                <th>Empleado</th>
                <th>Sucursal</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($pedidos as $pedido)
                <tr>
                    <td>{{ $pedido->cod_pedido }}</td>
                    <td>{{ $pedido->fec_pedido ? \Carbon\Carbon::parse($pedido->fec_pedido)->format('d/m/Y') : '-' }}</td>
                    <td>{{ $pedido->ped_empleados?->persona?->nombre_completo ?? '-' }}</td>
                    <td>{{ $pedido->sucursal_ped?->descripcion ?? '-' }}</td>
                    <td>{{ $pedido->estado ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No se encontraron pedidos con los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
