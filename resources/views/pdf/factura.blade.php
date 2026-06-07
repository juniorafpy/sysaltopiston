<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $factura->numero_factura }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #222; padding: 12px; margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 3px 6px; }
        .bordered { border: 1.5px solid #222; }
        .bb { border-bottom: 1.5px solid #222; }
        .bt { border-top: 1.5px solid #222; }
        .bl { border-left: 1.5px solid #222; }
        .br { border-right: 1.5px solid #222; }
        .bg-dark { background: #222; color: #fff; }
        .bg-light { background: #f0f0f0; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .bold { font-weight: bold; }
        .company-name { font-size: 16px; font-weight: bold; }
        .company-desc { font-size: 8px; }
        .title-doc { font-size: 14px; font-weight: bold; }
        .total-general { font-size: 14px; font-weight: bold; }
        .no-pad { padding: 0; }
        .px2 { padding-left: 8px; padding-right: 8px; }
        .py1 { padding-top: 2px; padding-bottom: 2px; }
        .py2 { padding-top: 5px; padding-bottom: 5px; }
        .w50 { width: 50%; }
        .vtop { vertical-align: top; }
    </style>
</head>
<body>

    {{-- ═══ HEADER: COMPANY ═══ --}}
    <table class="bordered">
        <tr>
            <td class="text-center py2">
                <div class="company-name">ALTOPISTON</div>
                <div class="company-desc">De: ALEXIS JUNIOR ALVAREZ FIGUEREDO</div>
                <div class="company-desc">Venta, Mantenimiento y Reparación de Vehículos Automotores</div>
                <div style="font-size:8px; margin-top:2px;">
                    RUC: <span style="font-weight:bold;">[TU_RUC]</span>
                    &nbsp;&nbsp;|&nbsp;&nbsp;
                    Teléfono: <span style="font-weight:bold;">[TU_TEL]</span>
                </div>
                <div style="font-size:8px;">
                    Dirección: <span style="font-weight:bold;">[TU_DIRECCION - Mariano Roque Alonso]</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- ═══ TIMBRADO & DOC INFO ═══ --}}
    <table class="bordered" style="margin-top:6px;">
        <tr>
            <td class="w50 vtop py1 px2">
                <div class="bold" style="font-size:10px;">TIMBRADO NRO: {{ $factura->timbrado->numero_timbrado ?? '—' }}</div>
                <div style="font-size:8px;">
                    Vigencia Inicio: {{ $factura->timbrado?->fecha_inicio_vigencia?->format('d/m/Y') ?? '—' }}
                </div>
                <div style="font-size:8px;">
                    Vigencia Fin: {{ $factura->timbrado?->fecha_fin_vigencia?->format('d/m/Y') ?? '—' }}
                </div>
            </td>
            <td class="w50 vtop py1 px2">
                <div class="bold" style="font-size:10px;">DOCUMENTO: FACTURA</div>
                <div style="font-size:9px;">
                    Nro: <span class="bold">{{ $factura->numero_factura }}</span>
                </div>
                <div style="font-size:9px;">
                    Condición: <span class="bold">{{ strtoupper($factura->condicion_venta ?? '—') }}</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- ═══ CLIENT INFO ═══ --}}
    <table class="bordered" style="margin-top:6px;">
        <tr>
            <td class="w50 vtop py1 px2">
                <div style="font-size:9px;">
                    <span class="bold">CLIENTE:</span>
                    {{ $factura->cliente->nombre_completo ?? '—' }}
                </div>
                <div style="font-size:9px;">
                    <span class="bold">RUC/CI:</span>
                    {{ $factura->cliente->nro_documento ?? '—' }}
                </div>
            </td>
            <td class="w50 vtop py1 px2">
                <div style="font-size:9px;">
                    <span class="bold">FECHA:</span>
                    {{ \Carbon\Carbon::parse($factura->fecha_factura)->format('d/m/Y') }}
                </div>
                <div style="font-size:9px;">
                    <span class="bold">TEL/DIR:</span>
                    {{ $factura->cliente->direccion ?? '—' }}
                </div>
            </td>
        </tr>
    </table>

    {{-- ═══ DETAILS TABLE ═══ --}}
    <table class="bordered" style="margin-top:6px;">
        <thead>
            <tr class="bg-dark">
                <th style="width:8%; padding:4px 3px; text-align:center;">COD.</th>
                <th style="width:42%; padding:4px 3px; text-align:left;">DESCRIPCIÓN</th>
                <th style="width:9%; padding:4px 3px; text-align:center;">CANT</th>
                <th style="width:16%; padding:4px 3px; text-align:right;">P. UNIT.</th>
                <th style="width:10%; padding:4px 3px; text-align:center;">IVA</th>
                <th style="width:15%; padding:4px 3px; text-align:right;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @forelse($factura->detalles as $det)
            <tr>
                <td class="text-center" style="padding:3px 2px;">{{ str_pad($det->cod_articulo, 3, '0', STR_PAD_LEFT) }}</td>
                <td style="padding:3px 2px;">{{ $det->descripcion ?? '—' }}</td>
                <td class="text-center" style="padding:3px 2px;">{{ $det->cantidad }}</td>
                <td class="text-right" style="padding:3px 2px;">{{ number_format($det->precio_unitario, 0, ',', '.') }}</td>
                <td class="text-center" style="padding:3px 2px;">{{ $det->tipo_iva === 'Exenta' ? 'Ex.' : $det->tipo_iva . '%' }}</td>
                <td class="text-right" style="padding:3px 2px;">{{ number_format($det->total, 0, ',', '.') }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center" style="padding:10px;">Sin detalles</td></tr>
            @endforelse

            {{-- Fill empty rows for spacing --}}
            @php $fillRows = max(0, 3 - $factura->detalles->count()); @endphp
            @for($i = 0; $i < $fillRows; $i++)
            <tr><td colspan="6" style="padding:5px;">&nbsp;</td></tr>
            @endfor
        </tbody>
    </table>

    {{-- ═══ LIQUIDACIÓN DEL IVA ═══ --}}
    <table class="bordered" style="margin-top:6px;">
        <tr><td class="bg-dark bold text-center py1" colspan="2" style="font-size:9px;">LIQUIDACIÓN DEL IVA</td></tr>
        @if($factura->total_iva_5 > 0)
        <tr>
            <td class="px2 py1" style="width:60%;">Total IVA 5%:</td>
            <td class="text-right px2 py1" style="width:40%;">Gs. {{ number_format($factura->total_iva_5, 0, ',', '.') }}</td>
        </tr>
        @endif
        @if($factura->total_iva_10 > 0)
        <tr>
            <td class="px2 py1" style="width:60%;">Total IVA 10%:</td>
            <td class="text-right px2 py1" style="width:40%;">Gs. {{ number_format($factura->total_iva_10, 0, ',', '.') }}</td>
        </tr>
        @endif
        <tr class="bg-light">
            <td class="px2 py1" style="width:60%; font-weight:bold;">Total IVA:</td>
            <td class="text-right px2 py1" style="width:40%; font-weight:bold;">
                Gs. {{ number_format(($factura->total_iva_5 ?? 0) + ($factura->total_iva_10 ?? 0), 0, ',', '.') }}
            </td>
        </tr>
        <tr class="bg-dark">
            <td class="px2 py1" style="width:60%; font-size:12px; font-weight:bold;">TOTAL GENERAL A PAGAR</td>
            <td class="text-right px2 py1" style="width:40%; font-size:13px; font-weight:bold;">
                Gs. {{ number_format($factura->total_general, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    {{-- ═══ OBSERVATIONS ═══ --}}
    @if($factura->observaciones)
    <table style="margin-top:6px;">
        <tr>
            <td style="padding:4px 6px; border:1px solid #999; font-size:8px; background:#fafafa;">
                <span class="bold">Obs.:</span> {{ $factura->observaciones }}
            </td>
        </tr>
    </table>
    @endif

    {{-- ═══ FOOTER ═══ --}}
    <table style="margin-top:12px;">
        <tr>
            <td class="text-center" style="font-size:7px; color:#888; padding-top:5px; border-top:1px solid #ccc;">
                Documento Electrónico generado desde el Sistema<br>
                {{ $factura->timbrado->numero_timbrado ?? '' }} | {{ $factura->numero_factura }}
            </td>
        </tr>
    </table>

</body>
</html>
