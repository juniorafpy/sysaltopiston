<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Proveedores</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            margin: 24px;
        }

        h1 {
            margin: 0 0 6px 0;
            font-size: 18px;
        }

        .meta {
            margin-bottom: 14px;
            color: #4b5563;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            font-weight: 700;
        }

        .estado-activo {
            color: #065f46;
            font-weight: 700;
        }

        .estado-inactivo {
            color: #991b1b;
            font-weight: 700;
        }
    </style>
</head>
<body>
    <h1>Listado de Proveedores</h1>
    <div class="meta">
        Generado: {{ $fecha->format('d/m/Y H:i') }}<br>
        Total de proveedores: {{ $proveedores->count() }}
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nombre / Razón Social</th>
                <th>Documento</th>
                <th>Estado</th>
                <th>Registrado por</th>
                <th>Fecha Alta</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($proveedores as $proveedor)
                @php
                    $persona = $proveedor->personas_pro;
                    $nombre = $persona?->razon_social ?: trim(($persona?->nombres ?? '') . ' ' . ($persona?->apellidos ?? ''));
                    $documento = $persona?->nro_documento ?? $persona?->ci_ruc ?? '-';
                    $estadoActivo = (bool) $proveedor->estado;
                @endphp
                <tr>
                    <td>{{ $proveedor->cod_proveedor }}</td>
                    <td>{{ $nombre ?: '-' }}</td>
                    <td>{{ $documento }}</td>
                    <td class="{{ $estadoActivo ? 'estado-activo' : 'estado-inactivo' }}">
                        {{ $estadoActivo ? 'Activo' : 'Inactivo' }}
                    </td>
                    <td>{{ $proveedor->usuario_alta ?? '-' }}</td>
                    <td>{{ optional($proveedor->fec_alta)->format('d/m/Y H:i') ?? ($proveedor->fec_alta ? \Carbon\Carbon::parse($proveedor->fec_alta)->format('d/m/Y H:i') : '-') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No hay proveedores registrados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
