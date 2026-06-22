<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Manual de Usuario - Proveedores</title>
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
    <h1>Manual de Usuario - Módulo Proveedores</h1>

    <div class="section">
        <h2>1. Descripción General</h2>
        <p>El módulo <strong>Proveedores</strong> permite registrar y gestionar los proveedores de repuestos y servicios del taller. Cada proveedor debe estar vinculado a una persona previamente registrada en el sistema.</p>
    </div>

    <div class="section">
        <h2>2. Acceso al Módulo</h2>
        <div class="step">
            <strong>Paso 1:</strong> En el menú lateral, despliegue el grupo <strong>"Referenciales/Compras"</strong>.<br>
            <strong>Paso 2:</strong> Haga clic en <strong>"Proveedores"</strong>.
        </div>
    </div>

    <div class="section">
        <h2>3. Requisito Previo</h2>
        <div class="note">
            Antes de crear un proveedor, debe existir la <strong>Persona</strong> en el sistema. Si no existe, vaya a Referenciales > Personas y créela primero.
        </div>
    </div>

    <div class="section">
        <h2>4. Crear un Proveedor</h2>
        <div class="step">
            <strong>Paso 1:</strong> Presione el botón <strong>"Nuevo Proveedor"</strong>.<br>
            <strong>Paso 2:</strong> Complete los campos:
        </div>

        <table class="field-table">
            <tr><th>Campo</th><th>Descripción</th><th>Obligatorio</th></tr>
            <tr><td>Persona</td><td>Seleccione la persona del proveedor del listado</td><td>Sí</td></tr>
            <tr><td>Estado Activo</td><td>Activo / Inactivo</td><td>Sí</td></tr>
        </table>

        <div class="step">
            <strong>Paso 3:</strong> Presione <strong>"Crear"</strong>.<br>
            <strong>Paso 4:</strong> El sistema confirmará y volverá al listado.
        </div>
    </div>

    <div class="section">
        <h2>5. Editar un Proveedor</h2>
        <div class="step">
            <strong>Paso 1:</strong> Busque el proveedor en el listado.<br>
            <strong>Paso 2:</strong> Haga clic en <strong>"Editar"</strong>.<br>
            <strong>Paso 3:</strong> Modifique los datos y guarde.
        </div>
    </div>

    <div class="section">
        <h2>6. Eliminar un Proveedor</h2>
        <div class="step">
            <strong>Paso 1:</strong> Haga clic en <strong>"Eliminar"</strong> junto al proveedor.<br>
            <strong>Paso 2:</strong> Confirme la acción.
        </div>
        <div class="note">
            <strong>Nota:</strong> No se puede eliminar un proveedor que tenga compras, facturas o notas de crédito asociadas.
        </div>
    </div>

    <div class="footer">
        Sistema AltoPiston - Manual de Usuario | Generado el {{ now()->format('d/m/Y') }}
    </div>
</body>
</html>
