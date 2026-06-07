<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Manual — Pedido de Compra</title>
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

<h1>Manual de Uso — Pedido de Compra</h1>

<h2>Propósito</h2>
<p>Registrar una solicitud interna de compra de productos (insumos, repuestos, etc.) que deben adquirirse de un proveedor. El pedido queda guardado con estado <strong>PENDIENTE</strong> y luego puede ser <em>Aprobado</em> o <em>Anulado</em> desde la lista.</p>

<h2>Pasos a seguir</h2>
<ol>
    <li><strong>Empleado</strong> — Seleccioná el empleado que realiza el pedido (suele cargarse automáticamente si ya iniciaste sesión).</li>
    <li><strong>Sucursal</strong> — Se completa automáticamente con tu sucursal asignada.</li>
    <li><strong>Fecha del Pedido</strong> — Se asigna la fecha actual por defecto. Podés cambiarla si es necesario.</li>
    <li><strong>Detalle del Pedido</strong> — Agregá uno o más productos haciendo clic en el botón "+":
        <ul>
            <li><strong>Producto</strong> — Buscá y seleccioná el artículo deseado. Al elegirlo, se mostrará automáticamente el stock disponible en tu sucursal.</li>
            <li><strong>Stock Disp.</strong> — Solo lectura. Muestra el saldo actual disponible del producto.</li>
            <li><strong>Cantidad</strong> — Ingresá la cantidad que deseás pedir (mínimo 1).</li>
        </ul>
    </li>
    <li>Hacé clic en <strong>Guardar</strong> para registrar el pedido.</li>
</ol>

<h2>Consideraciones importantes</h2>
<ul>
    <li>El pedido se crea con estado <strong>PENDIENTE</strong>. Para confirmarlo, debés usar la acción <em>Aprobar</em> desde la tabla de pedidos.</li>
    <li>Una vez <strong>Aprobado</strong>, no se puede editar ni eliminar. Tampoco se puede anular.</li>
    <li>Si el pedido está <strong>PENDIENTE</strong>, se puede <em>Editar</em> o <em>Anular</em> desde la lista.</li>
    <li>La columna <strong>Stock Disp.</strong> solo muestra el inventario al momento de cargar el producto. Si cerraste y volviste a abrir el formulario, el valor se actualiza al seleccionar el artículo nuevamente.</li>
    <li>No completar ningún campo obligatorio (Empleado, Sucursal, Fecha, al menos un producto con cantidad) impedirá guardar el pedido.</li>
</ul>

<div class="box">
    <strong>💡 Sugerencia:</strong> Para pedidos con muchos productos, completá primero todos los productos deseados y revisá las cantidades antes de hacer clic en <em>Guardar</em>.
</div>

<footer>SmartTain / ALTOPISTON — Sistema de Gestión &bull; Versión 1.0</footer>

</body>
</html>
