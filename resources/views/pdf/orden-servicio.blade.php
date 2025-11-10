<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Servicio #{{ $ordenServicio->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            font-size: 10pt;
            color: #333;
            line-height: 1.4;
        }

        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-flex {
            display: table;
            width: 100%;
        }

        .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }

        .company-name {
            font-size: 20pt;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 9pt;
            color: #666;
            line-height: 1.5;
        }

        .os-number {
            font-size: 16pt;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }

        .os-date {
            font-size: 9pt;
            color: #666;
        }

        .section-title {
            background-color: #f3f4f6;
            padding: 8px 10px;
            font-weight: bold;
            font-size: 11pt;
            color: #1f2937;
            margin-top: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #2563eb;
        }

        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            width: 25%;
            padding: 5px 10px 5px 0;
            font-weight: bold;
            color: #4b5563;
        }

        .info-value {
            display: table-cell;
            padding: 5px 0;
            color: #1f2937;
        }

        .vehicle-box {
            background-color: #eff6ff;
            border: 1px solid #bfdbfe;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
        }

        .vehicle-detail {
            display: inline-block;
            margin-right: 20px;
            margin-bottom: 5px;
        }

        .vehicle-label {
            font-weight: bold;
            color: #1e40af;
            font-size: 9pt;
        }

        .vehicle-value {
            color: #1f2937;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        table thead {
            background-color: #2563eb;
            color: white;
        }

        table thead th {
            padding: 8px 5px;
            text-align: left;
            font-size: 9pt;
            font-weight: bold;
        }

        table tbody td {
            padding: 6px 5px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 9pt;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        table tbody tr:hover {
            background-color: #f3f4f6;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .totals-table {
            width: 40%;
            float: right;
            margin-top: 10px;
        }

        .totals-table td {
            padding: 5px 10px;
            border: none;
        }

        .totals-label {
            font-weight: bold;
            color: #4b5563;
            text-align: right;
        }

        .totals-value {
            text-align: right;
            color: #1f2937;
        }

        .total-final {
            border-top: 2px solid #2563eb;
            font-size: 12pt;
            font-weight: bold;
            color: #2563eb;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8pt;
            font-weight: bold;
        }

        .badge-pendiente {
            background-color: #fef3c7;
            color: #92400e;
        }

        .badge-proceso {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .badge-completado {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-facturado {
            background-color: #e0e7ff;
            color: #3730a3;
        }

        .badge-cancelado {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .observations {
            background-color: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 5px;
            padding: 10px;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .observations-title {
            font-weight: bold;
            color: #92400e;
            margin-bottom: 5px;
        }

        .observations-text {
            color: #78350f;
            font-size: 9pt;
            white-space: pre-wrap;
        }

        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 50px;
            border-top: 2px solid #e5e7eb;
            padding-top: 10px;
            text-align: center;
            font-size: 8pt;
            color: #6b7280;
        }

        .page-number:after {
            content: "Página " counter(page);
        }

        .clearfix {
            clear: both;
        }

        .stock-badge {
            font-size: 8pt;
            padding: 2px 6px;
            border-radius: 3px;
        }

        .stock-reservado {
            background-color: #d1fae5;
            color: #065f46;
        }

        .stock-no-reservado {
            background-color: #fee2e2;
            color: #991b1b;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-flex">
            <div class="header-left">
                <div class="company-name">SysAltoPiston</div>
                <div class="company-info">
                    Taller Mecánico Automotriz<br>
                    Dirección: Av. Principal 1234<br>
                    Teléfono: (021) 123-4567<br>
                    Email: info@sysaltopiston.com
                </div>
            </div>
            <div class="header-right">
                <div class="os-number">Orden de Servicio #{{ $ordenServicio->id }}</div>
                <div class="os-date">
                    Fecha: {{ \Carbon\Carbon::parse($ordenServicio->fec_alta)->format('d/m/Y H:i') }}<br>
                    Sucursal: {{ $ordenServicio->sucursal->descripcion ?? 'N/A' }}
                </div>
                <div style="margin-top: 10px;">
                    @php
                        $estadoClass = match($ordenServicio->estado_trabajo) {
                            'Pendiente' => 'badge-pendiente',
                            'En Proceso' => 'badge-proceso',
                            'Completado' => 'badge-completado',
                            'Facturado' => 'badge-facturado',
                            'Cancelado' => 'badge-cancelado',
                            default => 'badge-pendiente'
                        };
                    @endphp
                    <span class="badge {{ $estadoClass }}">{{ $ordenServicio->estado_trabajo }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Cliente y Presupuesto -->
    <div class="section-title">📋 Información del Cliente y Presupuesto</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Cliente:</div>
            <div class="info-value">{{ $ordenServicio->cliente->nombre_completo ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Documento:</div>
            <div class="info-value">{{ $ordenServicio->cliente->nro_documento ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Teléfono:</div>
            <div class="info-value">{{ $ordenServicio->cliente->telefono ?? 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Presupuesto:</div>
            <div class="info-value">#{{ $ordenServicio->presupuesto_venta_id }}</div>
        </div>
    </div>

    <!-- Información del Vehículo -->
    <div class="section-title">🚗 Información del Vehículo</div>
    <div class="vehicle-box">
        @if($ordenServicio->recepcionVehiculo && $ordenServicio->recepcionVehiculo->vehiculo)
            @php $vehiculo = $ordenServicio->recepcionVehiculo->vehiculo; @endphp
            <div class="vehicle-detail">
                <span class="vehicle-label">Matrícula:</span>
                <span class="vehicle-value">{{ $vehiculo->matricula }}</span>
            </div>
            <div class="vehicle-detail">
                <span class="vehicle-label">Marca:</span>
                <span class="vehicle-value">{{ $vehiculo->marca->descripcion ?? 'N/A' }}</span>
            </div>
            <div class="vehicle-detail">
                <span class="vehicle-label">Modelo:</span>
                <span class="vehicle-value">{{ $vehiculo->modelo->descripcion ?? 'N/A' }}</span>
            </div>
            <div class="vehicle-detail">
                <span class="vehicle-label">Año:</span>
                <span class="vehicle-value">{{ $vehiculo->anio }}</span>
            </div>
            <div class="vehicle-detail">
                <span class="vehicle-label">Color:</span>
                <span class="vehicle-value">{{ $vehiculo->color }}</span>
            </div>
            <div class="vehicle-detail">
                <span class="vehicle-label">Kilometraje:</span>
                <span class="vehicle-value">{{ number_format($ordenServicio->recepcionVehiculo->kilometraje) }} km</span>
            </div>
        @else
            <p>No hay información del vehículo disponible</p>
        @endif
    </div>

    <!-- Diagnóstico -->
    @if($ordenServicio->diagnostico)
    <div class="section-title">🔧 Diagnóstico Mecánico</div>
    <div class="observations">
        <div class="observations-text">{{ $ordenServicio->diagnostico->diagnostico_mecanico }}</div>
    </div>
    @endif

    <!-- Información del Servicio -->
    <div class="section-title">⚙️ Detalles del Servicio</div>
    <div class="info-grid">
        <div class="info-row">
            <div class="info-label">Mecánico Asignado:</div>
            <div class="info-value">{{ $ordenServicio->mecanicoAsignado->persona->nombre_completo ?? 'No asignado' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Fecha Inicio:</div>
            <div class="info-value">{{ $ordenServicio->fecha_inicio ? \Carbon\Carbon::parse($ordenServicio->fecha_inicio)->format('d/m/Y') : 'N/A' }}</div>
        </div>
        <div class="info-row">
            <div class="info-label">Fecha Est. Finalización:</div>
            <div class="info-value">{{ $ordenServicio->fecha_estimada_finalizacion ? \Carbon\Carbon::parse($ordenServicio->fecha_estimada_finalizacion)->format('d/m/Y') : 'N/A' }}</div>
        </div>
        @if($ordenServicio->fecha_finalizacion_real)
        <div class="info-row">
            <div class="info-label">Fecha Real Finalización:</div>
            <div class="info-value">{{ \Carbon\Carbon::parse($ordenServicio->fecha_finalizacion_real)->format('d/m/Y H:i') }}</div>
        </div>
        @endif
    </div>

    <!-- Detalle de Artículos -->
    <div class="section-title">🛒 Detalle de Artículos y Servicios</div>
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 35%;">Artículo</th>
                <th style="width: 8%;" class="text-center">Cant.</th>
                <th style="width: 12%;" class="text-right">Precio Unit.</th>
                <th style="width: 8%;" class="text-center">Desc.</th>
                <th style="width: 12%;" class="text-right">Subtotal</th>
                <th style="width: 8%;" class="text-center">IVA</th>
                <th style="width: 12%;" class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ordenServicio->detalles as $index => $detalle)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>
                    {{ $detalle->articulo->descripcion ?? $detalle->descripcion }}
                    <br>
                    <small style="color: #6b7280;">
                        Código: {{ $detalle->cod_articulo }}
                        @if($detalle->presupuesto_venta_detalle_id)
                            | 🔒 Del presupuesto
                        @else
                            | 🆕 Adicional
                        @endif
                    </small>
                    <br>
                    <span class="stock-badge {{ $detalle->stock_reservado ? 'stock-reservado' : 'stock-no-reservado' }}">
                        {{ $detalle->stock_reservado ? '✓ Stock reservado' : '✗ Sin reserva' }}
                    </span>
                </td>
                <td class="text-center">{{ number_format($detalle->cantidad, 0) }}</td>
                <td class="text-right">{{ number_format($detalle->precio_unitario, 0) }}</td>
                <td class="text-center">{{ $detalle->porcentaje_descuento }}%</td>
                <td class="text-right">{{ number_format($detalle->subtotal, 0) }}</td>
                <td class="text-center">{{ $detalle->porcentaje_impuesto }}%</td>
                <td class="text-right"><strong>{{ number_format($detalle->total, 0) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totales -->
    <div class="clearfix">
        <table class="totals-table">
            <tr>
                <td class="totals-label">Subtotal:</td>
                <td class="totals-value">Gs. {{ number_format($ordenServicio->detalles->sum('subtotal'), 0) }}</td>
            </tr>
            <tr>
                <td class="totals-label">Descuentos:</td>
                <td class="totals-value">Gs. {{ number_format($ordenServicio->detalles->sum('monto_descuento'), 0) }}</td>
            </tr>
            <tr>
                <td class="totals-label">IVA (10%):</td>
                <td class="totals-value">Gs. {{ number_format($ordenServicio->detalles->sum('monto_impuesto'), 0) }}</td>
            </tr>
            <tr class="total-final">
                <td class="totals-label">TOTAL:</td>
                <td class="totals-value">Gs. {{ number_format($ordenServicio->total, 0) }}</td>
            </tr>
        </table>
    </div>
    <div class="clearfix"></div>

    <!-- Observaciones Técnicas -->
    @if($ordenServicio->observaciones_tecnicas)
    <div class="section-title">📝 Observaciones Técnicas</div>
    <div class="observations">
        <div class="observations-text">{{ $ordenServicio->observaciones_tecnicas }}</div>
    </div>
    @endif

    <!-- Observaciones Internas -->
    @if($ordenServicio->observaciones_internas)
    <div class="section-title">🔒 Observaciones Internas</div>
    <div class="observations" style="background-color: #f3f4f6; border-color: #d1d5db;">
        <div class="observations-text" style="color: #374151;">{{ $ordenServicio->observaciones_internas }}</div>
    </div>
    @endif

    <!-- Firmas -->
    <div style="margin-top: 40px;">
        <table style="width: 100%;">
            <tr>
                <td style="width: 50%; text-align: center; border: none; padding: 20px;">
                    <div style="border-top: 1px solid #333; padding-top: 5px; margin-top: 60px;">
                        <strong>Firma del Cliente</strong><br>
                        <small>{{ $ordenServicio->cliente->nombre_completo ?? 'N/A' }}</small>
                    </div>
                </td>
                <td style="width: 50%; text-align: center; border: none; padding: 20px;">
                    <div style="border-top: 1px solid #333; padding-top: 5px; margin-top: 60px;">
                        <strong>Firma del Mecánico</strong><br>
                        <small>{{ $ordenServicio->mecanicoAsignado->persona->nombre_completo ?? 'N/A' }}</small>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>
            Este documento es una orden de servicio oficial de SysAltoPiston<br>
            Generado el {{ now()->format('d/m/Y H:i:s') }} por {{ auth()->user()->name ?? 'Sistema' }}
        </p>
        <div class="page-number"></div>
    </div>
</body>
</html>
