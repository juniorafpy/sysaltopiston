<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Entrega #{{ $d['id'] }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 9pt;
            color: #1f2937;
            line-height: 1.4;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 14pt;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header p {
            font-size: 8pt;
            color: #6b7280;
            margin-top: 3px;
        }
        .badge {
            display: inline-block;
            background: #2563eb;
            color: #fff;
            padding: 2px 10px;
            border-radius: 3px;
            font-size: 10pt;
            font-weight: bold;
            margin-top: 5px;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .info-grid td {
            padding: 4px 6px;
            vertical-align: top;
        }
        .info-grid .label {
            font-weight: bold;
            color: #4b5563;
            width: 35%;
            font-size: 8pt;
            text-transform: uppercase;
        }
        .info-grid .value {
            color: #1f2937;
            width: 65%;
        }
        .section-title {
            background: #f3f4f6;
            padding: 5px 8px;
            font-size: 9pt;
            font-weight: bold;
            color: #374151;
            text-transform: uppercase;
            border-bottom: 1px solid #d1d5db;
            margin-top: 10px;
            margin-bottom: 6px;
        }
        .divider {
            border-top: 1px dashed #d1d5db;
            margin: 12px 0;
        }
        .firma-area {
            margin-top: 30px;
        }
        .firma-area .section-title {
            margin-bottom: 15px;
        }
        .firma-line {
            display: inline-block;
            width: 50%;
            border-top: 1px solid #1f2937;
            padding-top: 4px;
            margin-top: 40px;
            text-align: center;
            font-size: 8pt;
            color: #6b7280;
        }
        .firma-row {
            display: table;
            width: 100%;
        }
        .firma-col {
            display: table-cell;
            width: 50%;
            text-align: center;
            vertical-align: bottom;
        }
        .firma-col .line {
            border-top: 1px solid #1f2937;
            margin: 0 20px;
            padding-top: 4px;
            font-size: 8pt;
            color: #6b7280;
        }
        .firma-col .label {
            font-size: 7pt;
            color: #9ca3af;
            text-transform: uppercase;
            margin-bottom: 2px;
        }
        .observaciones {
            background: #f9fafb;
            padding: 8px;
            border: 1px solid #e5e7eb;
            border-radius: 3px;
            font-size: 8.5pt;
            min-height: 40px;
        }
        .footer {
            text-align: center;
            font-size: 7pt;
            color: #9ca3af;
            margin-top: 20px;
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>AltoPiston</h1>
        <p>Comprobante de Entrega de Vehículo</p>
        <span class="badge">ENTREGA #{{ str_pad($d['id'], 6, '0', STR_PAD_LEFT) }}</span>
    </div>

    <div class="section-title">Datos de la Orden de Servicio</div>
    <table class="info-grid">
        <tr>
            <td class="label">OS #</td>
            <td class="value">{{ $d['os_id'] ?? '—' }}</td>
            <td class="label">Fecha de Entrega</td>
            <td class="value">{{ $d['fecha_entrega']?->format('d/m/Y H:i') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Cliente</td>
            <td class="value">{{ $d['cliente_nombre'] ?? '—' }}</td>
            <td class="label">Documento</td>
            <td class="value">{{ $d['cliente_documento'] ?? '—' }}</td>
        </tr>
    </table>

    <div class="section-title">Datos del Vehículo</div>
    <table class="info-grid">
        <tr>
            <td class="label">Matrícula</td>
            <td class="value">{{ $d['matricula'] ?? '—' }}</td>
            <td class="label">Año</td>
            <td class="value">{{ $d['anio'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Marca</td>
            <td class="value">{{ $d['marca'] ?? '—' }}</td>
            <td class="label">Modelo</td>
            <td class="value">{{ $d['modelo'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Km Ingreso</td>
            <td class="value">{{ $d['kilometraje_ingreso'] ? number_format($d['kilometraje_ingreso'], 0, ',', '.') . ' km' : '—' }}</td>
            <td class="label">Km Salida</td>
            <td class="value">{{ $d['kilometraje_salida'] ? number_format($d['kilometraje_salida'], 0, ',', '.') . ' km' : '—' }}</td>
        </tr>
    </table>

    <div class="section-title">Recepción del Vehículo</div>
    <table class="info-grid">
        <tr>
            <td class="label">Recibió</td>
            <td class="value">{{ $d['persona_recibe'] ?? '—' }}</td>
            <td class="label">Documento</td>
            <td class="value">{{ $d['documento_recibe'] ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Recibe Titular</td>
            <td class="value">{{ $d['recibe_titular'] ? 'Sí' : 'No' }}</td>
            <td class="label">Registró</td>
            <td class="value">{{ $d['usuario_alta'] ?? '—' }}</td>
        </tr>
    </table>

    @if ($d['observaciones'])
        <div class="section-title">Observaciones</div>
        <div class="observaciones">{{ $d['observaciones'] }}</div>
    @endif

    <div class="divider"></div>

    <div class="firma-area">
        <div class="section-title">Firmas</div>
        <div class="firma-row">
            <div class="firma-col">
                <div class="label">Entregó</div>
                <div class="line">{{ $usuario }}</div>
            </div>
            <div class="firma-col">
                <div class="label">Recibí Conforme</div>
                <div class="line">{{ $d['persona_recibe'] ?? '______________' }}</div>
            </div>
        </div>
    </div>

    <div class="footer">
        AltoPiston — Comprobante de Entrega de Vehículo — {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
