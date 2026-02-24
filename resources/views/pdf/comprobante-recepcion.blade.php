<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Recepción</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        .header h2 {
            font-size: 14px;
            color: #666;
        }
        .info-section {
            margin-bottom: 15px;
        }
        .info-section h3 {
            background-color: #333;
            color: white;
            padding: 5px 10px;
            font-size: 12px;
            margin-bottom: 8px;
        }
        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            width: 30%;
            font-weight: bold;
            padding: 4px 8px;
            border: 1px solid #ddd;
            background-color: #f5f5f5;
        }
        .info-value {
            display: table-cell;
            width: 70%;
            padding: 4px 8px;
            border: 1px solid #ddd;
        }
        .inventario-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }
        .inventario-row {
            display: table-row;
        }
        .inventario-item {
            display: table-cell;
            width: 33.33%;
            padding: 5px;
            border: 1px solid #ddd;
        }
        .checkbox {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 1px solid #333;
            margin-right: 5px;
            vertical-align: middle;
        }
        .checkbox.checked {
            background-color: #333;
        }
        .observaciones-box {
            border: 1px solid #ddd;
            padding: 8px;
            min-height: 60px;
            background-color: #fafafa;
        }
        .firma-section {
            margin-top: 40px;
            display: table;
            width: 100%;
        }
        .firma-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 10px;
        }
        .firma-line {
            border-top: 1px solid #333;
            margin: 50px 20px 5px 20px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 9px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TALLER MECÁNICO - COMPROBANTE DE RECEPCIÓN</h1>
        <h2>Sistema Alto Pistón</h2>
        <p>Fecha de emisión: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <!-- Datos de la Recepción -->
    <div class="info-section">
        <h3>DATOS DE LA RECEPCIÓN</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nro. Recepción:</div>
                <div class="info-value"><strong>#{{ $recepcion->id }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Fecha y Hora:</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($recepcion->fecha_recepcion)->format('d/m/Y H:i:s') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Estado:</div>
                <div class="info-value">{{ $recepcion->estado }}</div>
            </div>
        </div>
    </div>

    <!-- Datos del Cliente -->
    <div class="info-section">
        <h3>DATOS DEL CLIENTE</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Cliente:</div>
                <div class="info-value">{{ $recepcion->cliente->nombre_completo }}</div>
            </div>
            @if($recepcion->cliente->persona)
            <div class="info-row">
                <div class="info-label">Documento:</div>
                <div class="info-value">{{ $recepcion->cliente->persona->ci ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Teléfono:</div>
                <div class="info-value">{{ $recepcion->cliente->persona->telefono ?? 'N/A' }}</div>
            </div>
            @endif
        </div>
    </div>

    <!-- Datos del Vehículo -->
    <div class="info-section">
        <h3>DATOS DEL VEHÍCULO</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Matrícula (Chapa):</div>
                <div class="info-value"><strong>{{ $recepcion->vehiculo->matricula }}</strong></div>
            </div>
            <div class="info-row">
                <div class="info-label">Marca / Modelo:</div>
                <div class="info-value">{{ $recepcion->vehiculo->modelo->marca->descripcion ?? 'N/A' }} - {{ $recepcion->vehiculo->modelo->descripcion ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Año:</div>
                <div class="info-value">{{ $recepcion->vehiculo->anio }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Color:</div>
                <div class="info-value">{{ $recepcion->vehiculo->color->descripcion ?? 'N/A' }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Kilometraje:</div>
                <div class="info-value">{{ number_format($recepcion->kilometraje, 0, ',', '.') }} km</div>
            </div>
        </div>
    </div>

    <!-- Motivo de Ingreso -->
    <div class="info-section">
        <h3>MOTIVO DE INGRESO</h3>
        <div class="observaciones-box">
            {{ $recepcion->motivo_ingreso }}
        </div>
    </div>

    <!-- Inventario del Vehículo -->
    @if($recepcion->inventario)
    <div class="info-section">
        <h3>INVENTARIO DEL VEHÍCULO</h3>
        <div class="inventario-grid">
            <div class="inventario-row">
                <div class="inventario-item">
                    <span class="checkbox {{ $recepcion->inventario->extintor ? 'checked' : '' }}"></span>
                    Extintor
                </div>
                <div class="inventario-item">
                    <span class="checkbox {{ $recepcion->inventario->valija ? 'checked' : '' }}"></span>
                    Valija
                </div>
                <div class="inventario-item">
                    <span class="checkbox {{ $recepcion->inventario->rueda_auxilio ? 'checked' : '' }}"></span>
                    Rueda de Auxilio
                </div>
            </div>
            <div class="inventario-row">
                <div class="inventario-item">
                    <span class="checkbox {{ $recepcion->inventario->gato ? 'checked' : '' }}"></span>
                    Gato
                </div>
                <div class="inventario-item">
                    <span class="checkbox {{ $recepcion->inventario->llave_ruedas ? 'checked' : '' }}"></span>
                    Llave de Ruedas
                </div>
                <div class="inventario-item">
                    <span class="checkbox {{ $recepcion->inventario->triangulos_seguridad ? 'checked' : '' }}"></span>
                    Triángulos
                </div>
            </div>
            <div class="inventario-row">
                <div class="inventario-item">
                    <span class="checkbox {{ $recepcion->inventario->botiquin ? 'checked' : '' }}"></span>
                    Botiquín
                </div>
                <div class="inventario-item">
                    <span class="checkbox {{ $recepcion->inventario->manual_vehiculo ? 'checked' : '' }}"></span>
                    Manual del Vehículo
                </div>
                <div class="inventario-item">
                    <span class="checkbox {{ $recepcion->inventario->llave_repuesto ? 'checked' : '' }}"></span>
                    Llave Repuesto
                </div>
            </div>
            <div class="inventario-row">
                <div class="inventario-item">
                    <span class="checkbox {{ $recepcion->inventario->radio_estereo ? 'checked' : '' }}"></span>
                    Radio/Estéreo
                </div>
                <div class="inventario-item" style="border-right: 1px solid #ddd;">
                    <strong>Nivel Combustible:</strong> {{ $recepcion->inventario->nivel_combustible ?? 'N/A' }}
                </div>
                <div class="inventario-item"></div>
            </div>
        </div>

        @if($recepcion->inventario->observaciones_inventario)
        <div style="margin-top: 10px;">
            <strong>Observaciones del Inventario:</strong>
            <div class="observaciones-box">
                {{ $recepcion->inventario->observaciones_inventario }}
            </div>
        </div>
        @endif
    </div>
    @endif

    <!-- Mecánico Asignado -->
    @if($recepcion->empleado)
    <div class="info-section">
        <h3>MECÁNICO ASIGNADO</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Mecánico:</div>
                <div class="info-value">
                    @if($recepcion->empleado->persona)
                        {{ $recepcion->empleado->persona->razon_social ?: trim($recepcion->empleado->persona->nombres . ' ' . $recepcion->empleado->persona->apellidos) }}
                    @else
                        {{ $recepcion->empleado->nombre }}
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Sección de Firmas -->
    <div class="firma-section">
        <div class="firma-box">
            <div class="firma-line"></div>
            <strong>FIRMA DEL CLIENTE</strong><br>
            <small>Acuso recibo y acepto las condiciones</small>
        </div>
        <div class="firma-box">
            <div class="firma-line"></div>
            <strong>FIRMA DEL RECEPTOR</strong><br>
            <small>{{ $recepcion->empleado ? ($recepcion->empleado->persona ? ($recepcion->empleado->persona->razon_social ?: trim($recepcion->empleado->persona->nombres . ' ' . $recepcion->empleado->persona->apellidos)) : $recepcion->empleado->nombre) : 'Taller' }}</small>
        </div>
    </div>

    <div class="footer">
        <p><strong>IMPORTANTE:</strong> El taller no se responsabiliza por objetos de valor dejados en el vehículo.</p>
        <p>Este documento constituye el comprobante oficial de recepción del vehículo.</p>
        <p>Conserve este comprobante para el retiro del vehículo.</p>
    </div>
</body>
</html>
