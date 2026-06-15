<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Manual — Presupuesto de Compra</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #1f2937;
            line-height: 1.6;
            padding: 30px;
        }
        h1 {
            font-size: 20px;
            color: #2563eb;
            border-bottom: 3px solid #2563eb;
            padding-bottom: 8px;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 15px;
            background: #eff6ff;
            color: #1e40af;
            padding: 8px 12px;
            border-radius: 6px;
            margin-top: 24px;
            margin-bottom: 10px;
        }
        ul, ol {
            padding-left: 22px;
            margin: 8px 0;
        }
        li { margin-bottom: 4px; }
        .box {
            background: #fefce8;
            border-left: 4px solid #eab308;
            padding: 10px 14px;
            border-radius: 6px;
            margin: 16px 0;
            font-size: 11px;
        }
        .box strong { color: #a16207; }
        .box-info {
            background: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 10px 14px;
            border-radius: 6px;
            margin: 16px 0;
            font-size: 11px;
        }
        .box-info strong { color: #1e40af; }
        footer {
            margin-top: 40px;
            padding-top: 12px;
            border-top: 1px solid #d1d5db;
            font-size: 10px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>

<h1>Manual de Uso — Presupuesto de Compra</h1>

<h2>Propósito</h2>
<p>Registrar un presupuesto de compra de productos a un proveedor. El presupuesto puede crearse manualmente o generarse automáticamente a partir de un <strong>Pedido de Compra Aprobado</strong>. Sirve como base para negociar precios y posteriormente generar una <em>Orden de Compra</em>.</p>

<h2>Pasos a seguir</h2>
<ol>
    <li><strong>Sucursal</strong> — Se completa automáticamente con tu sucursal asignada.</li>
    <li><strong>Fecha del Presupuesto</strong> — Se asigna la fecha actual por defecto. No es editable.</li>
    <li><strong>Proveedor</strong> — Buscá y seleccioná el proveedor al que se le solicitará el presupuesto. Es obligatorio.</li>
    <li><strong>Condición de Compra</strong> — Seleccioná la condición de pago (Contado, Crédito, etc.). Es obligatorio.</li>
    <li><strong>Pedido de Referencia (opcional)</strong> — Si deseás cargar los artículos desde un pedido ya aprobado:
        <ul>
            <li>Seleccioná el pedido de la lista. Solo aparecen los pedidos <strong>APROBADOS</strong> que aún no estén cargados en otro presupuesto.</li>
            <li>Al seleccionar el pedido, los artículos y cantidades se cargarán <strong>automáticamente</strong> en la tabla de detalles.</li>
            <li>Cuando usás un pedido de referencia, los artículos y cantidades están <em>bloqueados</em>. Solo podés editar los precios.</li>
        </ul>
    </li>
    <li><strong>Observación</strong> — Podés agregar comentarios o condiciones especiales del presupuesto.</li>
    <li><strong>Detalles del Presupuesto</strong> — Agregá uno o más artículos haciendo clic en el botón "+ Agregar Artículo":
        <ul>
            <li><strong>Artículo</strong> — Buscá y seleccioná el producto. Al elegirlo, el <em>precio</em> se carga automáticamente desde el catálogo.</li>
            <li><strong>Precio</strong> — Se carga automáticamente al seleccionar el artículo. Podés modificarlo si es necesario.</li>
            <li><strong>Cantidad</strong> — Ingresá la cantidad deseada (por defecto es 1). Al cambiarla, el total se recalcula automáticamente.</li>
            <li><strong>Total</strong> — Calculado automáticamente: <code>Precio × Cantidad</code>. No editable.</li>
            <li><strong>IVA 10%</strong> — Calculado automáticamente: <code>Total ÷ 11</code>. No editable.</li>
        </ul>
    </li>
    <li>Los totales de la cabecera (Monto Gravado, IVA Total, Monto General) se calculan <strong>automáticamente</strong> al agregar o modificar artículos.</li>
    <li>Hacé clic en <strong>Guardar</strong> para registrar el presupuesto.</li>
</ol>

<div class="box-info">
    <strong>ℹ️ Pedido de referencia:</strong> Si cargás un pedido de referencia, los artículos y cantidades se importan automáticamente y no se pueden modificar. Esto asegura que el presupuesto se base exactamente en lo que se solicitó en el pedido. Solo podés ajustar los precios.
</div>

<h2>Consideraciones importantes</h2>
<ul>
    <li>El presupuesto se crea con estado <strong>PENDIENTE</strong>.</li>
    <li>Una vez guardado, podés <em>Imprimir</em> o <em>Enviar por email</em> al proveedor desde la lista de presupuestos.</li>
    <li>Los precios pueden ajustarse libremente. El sistema usa el precio del catálogo como referencia, pero podés negociar un precio diferente.</li>
    <li>Los totales (Monto Gravado, IVA Total, Monto General) se actualizan en tiempo real mientras editás los detalles.</li>
    <li>No completar el <strong>Proveedor</strong> o la <strong>Condición de Compra</strong> impedirá guardar el presupuesto.</li>
    <li>Debe haber al menos <strong>un artículo</strong> con precio y cantidad válidos.</li>
</ul>

<div class="box">
    <strong>💡 Sugerencia:</strong> Si tenés un pedido de compra aprobado, usá la opción <em>Pedido de Referencia</em> para ahorrar tiempo. El sistema cargará todo automáticamente y solo tendrás que ajustar los precios según la cotización del proveedor.
</div>

<footer>SmartTain / ALTOPISTON — Sistema de Gestión &bull; Versión 1.0</footer>

</body>
</html>
