<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Orden de Compra</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #333; padding: 20px; }
        h1 { font-size: 24px; text-align: center; margin-bottom: 5px; }
        h2 { font-size: 18px; text-align: center; color: #666; margin-bottom: 20px; }
        h3 { font-size: 16px; margin: 20px 0 10px 0; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table th { padding: 10px; text-align: left; background-color: #3498db; color: white; }
        table td { padding: 8px; border-bottom: 1px solid #ddd; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .info-table { margin-bottom: 15px; }
        .info-table td { padding: 5px 10px; }
        .info-label { font-weight: bold; background-color: #ecf0f1; width: 25%; }
        .totals-table { width: 40%; float: right; margin-top: 20px; }
        .grand-total { background-color: #2c3e50; color: white; font-weight: bold; }
        .footer { margin-top: 80px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; font-size: 10px; color: #666; clear: both; }
        .observations { margin-top: 20px; padding: 10px; background-color: #f8f9fa; border-left: 4px solid #3498db; }
    </style>
</head>
<body>
    <h1>ORDEN DE COMPRA</h1>
    <h2>N° {{ $ordenCompra->nro_orden_compra }}</h2>

    <table class="info-table">
        <tr>
            <td class="info-label">Fecha de Orden:</td>
            <td>{{ $ordenCompra->fec_orden }}</td>
        </tr>
        <tr>
            <td class="info-label">Fecha de Entrega:</td>
            <td>{{ $ordenCompra->fec_entrega }}</td>
        </tr>
        @if($ordenCompra->nro_presupuesto_ref)
        <tr>
            <td class="info-label">Presupuesto Ref.:</td>
            <td>N° {{ $ordenCompra->nro_presupuesto_ref }}</td>
        </tr>
        @endif
        <tr>
            <td class="info-label">Estado:</td>
            <td>{{ optional($ordenCompra->estadoRel)->descripcion }}</td>
        </tr>
    </table>

    <h3>Proveedor</h3>
    <table class="info-table">
        <tr>
            <td class="info-label">Proveedor:</td>
            <td>{{ optional(optional($ordenCompra->proveedor)->personas_pro)->nombre_completo ?? optional($ordenCompra->proveedor)->razon_social ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="info-label">Condicion de Compra:</td>
            <td>{{ optional($ordenCompra->condicionCompra)->descripcion }}</td>
        </tr>
    </table>

    <h3>Detalle de Articulos</h3>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Artículo</th>
                <th class="text-center">Cantidad</th>
                <th class="text-right">Precio Unit.</th>
                <th class="text-right">Subtotal</th>
                <th class="text-right">IVA 10%</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ordenCompra->ordenCompraDetalles as $index => $detalle)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ optional($detalle->articulo)->descripcion }}</td>
                <td class="text-center">{{ $detalle->cantidad }}</td>
                <td class="text-right">{{ number_format($detalle->precio, 0) }} Gs.</td>
                <td class="text-right">{{ number_format($detalle->total, 0) }} Gs.</td>
                <td class="text-right">{{ number_format($detalle->total_iva, 0) }} Gs.</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">{{ number_format($subtotal, 0) }} Gs.</td>
        </tr>
        <tr>
            <td>IVA 10%:</td>
            <td class="text-right">{{ number_format($totalIva, 0) }} Gs.</td>
        </tr>
        <tr class="grand-total">
            <td>TOTAL:</td>
            <td class="text-right">{{ number_format($total, 0) }} Gs.</td>
        </tr>
    </table>

    @if($ordenCompra->observacion)
    <div class="observations">
        <strong>Observaciones:</strong><br>
        {{ $ordenCompra->observacion }}
    </div>
    @endif

    <div class="footer">
        <p>Documento generado</p>
        <p>Usuario: {{ $ordenCompra->usuario_alta ?? 'Sistema' }}</p>
    </div>
</body>
</html>
