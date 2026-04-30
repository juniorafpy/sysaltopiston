<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Presupuesto N° {{ $presupuesto->nro_presupuesto }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #333; padding: 20px; }
        h1 { font-size: 22px; text-align: center; margin-bottom: 4px; text-transform: uppercase; }
        h2 { font-size: 15px; text-align: center; color: #555; margin-bottom: 20px; }
        h3 { font-size: 13px; margin: 18px 0 8px 0; border-bottom: 2px solid #2980b9; padding-bottom: 4px; color: #2980b9; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        table th { padding: 8px 6px; text-align: left; background-color: #2980b9; color: white; font-size: 11px; }
        table td { padding: 6px; border-bottom: 1px solid #e0e0e0; }
        .text-right  { text-align: right; }
        .text-center { text-align: center; }
        .info-table td { padding: 4px 8px; }
        .info-label { font-weight: bold; background-color: #ecf0f1; width: 22%; }
        .totals-table { width: 42%; float: right; margin-top: 10px; }
        .totals-table td { padding: 5px 8px; }
        .grand-total { background-color: #2c3e50; color: white; font-weight: bold; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .badge-pendiente  { background: #f39c12; color: white; }
        .badge-aprobado   { background: #27ae60; color: white; }
        .badge-anulado    { background: #e74c3c; color: white; }
        .footer { margin-top: 60px; padding-top: 15px; border-top: 1px solid #ccc; text-align: center; font-size: 9px; color: #888; clear: both; }
        .observations { margin-top: 15px; padding: 8px 12px; background-color: #f8f9fa; border-left: 4px solid #2980b9; font-size: 11px; }
        .clearfix::after { content: ""; display: table; clear: both; }
        tr.alt { background-color: #f5f5f5; }
    </style>
</head>
<body>

    <h1>Presupuesto de Compra</h1>
    <h2>N° {{ $presupuesto->nro_presupuesto }}</h2>

    {{-- Información general --}}
    <h3>Información General</h3>
    <table class="info-table">
        <tr>
            <td class="info-label">Fecha:</td>
            <td>{{ $presupuesto->fec_presupuesto ? \Carbon\Carbon::parse($presupuesto->fec_presupuesto)->format('d/m/Y') : '—' }}</td>
            <td class="info-label">Estado:</td>
            <td>
                @php $estado = strtolower($presupuesto->estado ?? '') @endphp
                <span class="badge badge-{{ $estado }}">{{ strtoupper($presupuesto->estado ?? '—') }}</span>
            </td>
        </tr>
        <tr>
            <td class="info-label">Condición de Compra:</td>
            <td>
                @php $cc = $presupuesto->condicionCompra; @endphp
                @if($cc)
                    {{ (int)$cc->dias_cuotas === 0 ? 'CONTADO' : 'CRÉDITO ' . $cc->dias_cuotas . ' DÍAS' }}
                @else
                    —
                @endif
            </td>
            @if($presupuesto->nro_pedido_ref)
            <td class="info-label">Pedido Ref.:</td>
            <td>N° {{ $presupuesto->nro_pedido_ref }}</td>
            @else
            <td colspan="2"></td>
            @endif
        </tr>
    </table>

    {{-- Proveedor --}}
    <h3>Proveedor</h3>
    <table class="info-table">
        <tr>
            <td class="info-label">Razón Social / Nombre:</td>
            <td>{{ optional(optional($presupuesto->proveedor)->personas_pro)->nombre_completo ?? optional($presupuesto->proveedor)->razon_social ?? '—' }}</td>
        </tr>
    </table>

    {{-- Detalle de artículos --}}
    <h3>Detalle de Artículos</h3>
    <table>
        <thead>
            <tr>
                <th class="text-center" style="width:4%">#</th>
                <th style="width:44%">Artículo</th>
                <th class="text-center" style="width:8%">Cant.</th>
                <th class="text-right" style="width:15%">Precio Unit.</th>
                <th class="text-right" style="width:15%">Subtotal</th>
                <th class="text-right" style="width:14%">IVA 10%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($presupuesto->presupuestoDetalles as $index => $detalle)
            <tr class="{{ $index % 2 === 1 ? 'alt' : '' }}">
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ optional($detalle->articulo)->descripcion ?? $detalle->cod_articulo }}</td>
                <td class="text-center">{{ number_format((int)$detalle->cantidad, 0, ',', '.') }}</td>
                <td class="text-right">{{ number_format((float)$detalle->precio, 0, ',', '.') }} Gs.</td>
                <td class="text-right">{{ number_format((float)$detalle->total, 0, ',', '.') }} Gs.</td>
                <td class="text-right">{{ number_format((float)$detalle->total_iva, 0, ',', '.') }} Gs.</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Totales --}}
    <div class="clearfix">
        <table class="totals-table">
            <tr>
                <td>Gravado (sin IVA):</td>
                <td class="text-right">{{ number_format($subtotal, 0, ',', '.') }} Gs.</td>
            </tr>
            <tr>
                <td>IVA 10% (incluido):</td>
                <td class="text-right">{{ number_format($totalIva, 0, ',', '.') }} Gs.</td>
            </tr>
            <tr class="grand-total">
                <td>TOTAL GENERAL:</td>
                <td class="text-right">{{ number_format($total, 0, ',', '.') }} Gs.</td>
            </tr>
        </table>
    </div>

    @if($presupuesto->observacion)
    <div class="observations">
        <strong>Observaciones:</strong><br>
        {{ $presupuesto->observacion }}
    </div>
    @endif

    <div class="footer">
        <p>Documento generado el {{ now()->format('d/m/Y H:i') }} — Sistema Alto Pistón</p>
    </div>

</body>
</html>
