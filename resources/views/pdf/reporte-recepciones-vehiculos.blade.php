<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Recepciones de Vehículos</title>
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
    <h1>Reporte de Recepciones de Vehículos</h1>

    <div class="meta">
        Generado: {{ $fechaGeneracion->format('d/m/Y H:i') }}<br>
        Fecha desde: {{ $fechaDesde ? \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') : 'Todas' }}<br>
        Fecha hasta: {{ $fechaHasta ? \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') : 'Todas' }}<br>
        Estado: {{ $estado && $estado !== 'TODOS' ? $estado : 'Todos' }}<br>
        Total registros: {{ $recepciones->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Fecha Recepción</th>
                <th>Cliente</th>
                <th>Vehículo</th>
                <th>Mecánico</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($recepciones as $recepcion)
                <tr>
                    <td>{{ $recepcion->id }}</td>
                    <td>{{ $recepcion->fecha_recepcion ? \Carbon\Carbon::parse($recepcion->fecha_recepcion)->format('d/m/Y H:i') : '-' }}</td>
                    <td>{{ $recepcion->cliente?->nombre_completo ?? '-' }}</td>
                    <td>
                        @if($recepcion->vehiculo)
                            {{ $recepcion->vehiculo->matricula }} - {{ $recepcion->vehiculo->modelo->descripcion ?? 'Sin modelo' }}
                        @else
                            -
                        @endif
                    </td>
                    <td>
                        @php
                            $empleado = $recepcion->empleado;
                            $mecanico = '-';
                            if ($empleado?->persona) {
                                $mecanico = $empleado->persona->razon_social ?: trim(($empleado->persona->nombres ?? '') . ' ' . ($empleado->persona->apellidos ?? ''));
                            } elseif ($empleado?->nombre) {
                                $mecanico = $empleado->nombre;
                            }
                        @endphp
                        {{ $mecanico ?: '-' }}
                    </td>
                    <td>{{ $recepcion->estado ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No se encontraron recepciones con los filtros seleccionados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
