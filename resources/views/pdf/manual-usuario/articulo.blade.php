<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Manual de Usuario - Lista de Artículos</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; line-height: 1.4; color: #000; padding: 20px; }
        h1 { font-size: 16pt; text-align: center; border-bottom: 2px solid #000; padding-bottom: 5px; }
        h2 { font-size: 13pt; color: #1e3a8a; margin-top: 20px; }
        h3 { font-size: 11pt; color: #1e40af; margin-top: 15px; }
        .section { margin-bottom: 15px; }
        .step { background: #f3f4f6; padding: 8px; margin: 5px 0; border-left: 3px solid #1e3a8a; }
        .note { background: #fef3c7; padding: 6px; margin: 5px 0; border-left: 3px solid #f59e0b; font-size: 9pt; }
        .field-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .field-table th { background: #1e3a8a; color: #fff; padding: 6px; text-align: left; font-size: 9pt; }
        .field-table td { padding: 5px; border-bottom: 1px solid #ddd; font-size: 9pt; }
        .footer { text-align: center; font-size: 8pt; margin-top: 30px; border-top: 1px solid #ccc; padding-top: 5px; }
    </style>
</head>
<body>
    <h1>Manual de Usuario - Módulo Lista de Artículos</h1>

    <div class="section">
        <h2>1. Descripción General</h2>
        <p>El módulo <strong>Lista de Artículos</strong> permite gestionar el catálogo de repuestos, insumos y productos del taller. Cada artículo puede estar vinculado a una marca y tiene control de stock por sucursal.</p>
    </div>

    <div class="section">
        <h2>2. Acceso al Módulo</h2>
        <div class="step">
            <strong>Paso 1:</strong> En el menú lateral, despliegue <strong>"Referenciales/Compras"</strong>.<br>
            <strong>Paso 2:</strong> Haga clic en <strong>"Lista de Artículos"</strong>.
        </div>
    </div>

    <div class="section">
        <h2>3. Crear un Artículo</h2>
        <div class="step">
            <strong>Paso 1:</strong> Presione el botón <strong>"Nuevo Artículo"</strong>.<br>
            <strong>Paso 2:</strong> Complete la información del artículo:
        </div>

        <h3>Información General</h3>
        <table class="field-table">
            <tr><th>Campo</th><th>Descripción</th><th>Obligatorio</th></tr>
            <tr><td>Descripción</td><td>Nombre del artículo (Ej: Filtro de aceite)</td><td>Sí</td></tr>
            <tr><td>Activo</td><td>Indica si el artículo está disponible</td><td>Sí</td></tr>
            <tr><td>Marca</td><td>Marca del artículo (puede crear una nueva)</td><td>Sí</td></tr>
        </table>

        <h3>Clasificación</h3>
        <table class="field-table">
            <tr><th>Campo</th><th>Descripción</th><th>Obligatorio</th></tr>
            <tr><td>Tipo de Artículo</td><td>Categoría (Ej: Repuesto, Insumo, Servicio)</td><td>Sí</td></tr>
            <tr><td>Tipo de Repuesto</td><td>Subcategoría del repuesto</td><td>No</td></tr>
        </table>

        <h3>Precios y Costos</h3>
        <table class="field-table">
            <tr><th>Campo</th><th>Descripción</th><th>Obligatorio</th></tr>
            <tr><td>Precio Venta</td><td>Precio de venta al público</td><td>Sí</td></tr>
            <tr><td>Costo</td><td>Costo de compra</td><td>No</td></tr>
            <tr><td>Impuesto (IVA)</td><td>Porcentaje de IVA aplicable</td><td>Sí</td></tr>
        </table>

        <h3>Stock</h3>
        <table class="field-table">
            <tr><th>Campo</th><th>Descripción</th><th>Obligatorio</th></tr>
            <tr><td>Stock Mínimo</td><td>Cantidad mínima para alerta</td><td>Sí</td></tr>
            <tr><td>Stock Máximo</td><td>Cantidad máxima permitida</td><td>No</td></tr>
            <tr><td>Existencia Inicial</td><td>Cantidad con la que inicia</td><td>Sí</td></tr>
        </table>

        <div class="step">
            <strong>Paso 3:</strong> Presione <strong>"Crear"</strong> para guardar.<br>
            El sistema registrará automáticamente el usuario y fecha de alta.
        </div>
    </div>

    <div class="section">
        <h2>4. Crear Marca desde el Formulario</h2>
        <div class="step">
            <strong>Paso 1:</strong> En el campo <strong>Marca</strong>, haga clic en el botón <strong>"+"</strong>.<br>
            <strong>Paso 2:</strong> Ingrese el nombre de la nueva marca.<br>
            <strong>Paso 3:</strong> Presione <strong>"Crear"</strong>.<br>
            <strong>Paso 4:</strong> La marca se creará y se seleccionará automáticamente.
        </div>
    </div>

    <div class="section">
        <h2>5. Editar un Artículo</h2>
        <div class="step">
            <strong>Paso 1:</strong> En el listado, haga clic en <strong>"Editar"</strong>.<br>
            <strong>Paso 2:</strong> Modifique los campos necesarios.<br>
            <strong>Paso 3:</strong> Presione <strong>"Guardar"</strong>.
        </div>
    </div>

    <div class="section">
        <h2>6. Eliminar un Artículo</h2>
        <div class="step">
            <strong>Paso 1:</strong> Haga clic en <strong>"Eliminar"</strong>.<br>
            <strong>Paso 2:</strong> Confirme la acción.
        </div>
        <div class="note">
            <strong>Nota:</strong> No se puede eliminar un artículo que tenga movimientos de stock, compras o facturas asociadas.
        </div>
    </div>

    <div class="footer">
        Sistema AltoPiston - Manual de Usuario | Generado el {{ now()->format('d/m/Y') }}
    </div>
</body>
</html>
