<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Manual de Usuario - Marcas</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10pt; line-height: 1.4; color: #000; padding: 20px; }
        h1 { font-size: 16pt; text-align: center; border-bottom: 2px solid #000; padding-bottom: 5px; }
        h2 { font-size: 13pt; color: #1e3a8a; margin-top: 20px; }
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
    <h1>Manual de Usuario - Módulo Marcas</h1>

    <div class="section">
        <h2>1. Descripción General</h2>
        <p>El módulo <strong>Marcas</strong> permite gestionar las marcas de vehículos y repuestos. Las marcas se utilizan en el módulo de Modelos y en el catálogo de Artículos.</p>
    </div>

    <div class="section">
        <h2>2. Acceso al Módulo</h2>
        <div class="step">
            <strong>Paso 1:</strong> En el menú lateral, despliegue <strong>"Referenciales/Compras"</strong>.<br>
            <strong>Paso 2:</strong> Haga clic en <strong>"Marcas"</strong>.
        </div>
    </div>

    <div class="section">
        <h2>3. Crear una Marca</h2>
        <div class="step">
            <strong>Paso 1:</strong> Presione <strong>"Nueva Marca"</strong>.<br>
            <strong>Paso 2:</strong> Complete:
        </div>

        <table class="field-table">
            <tr><th>Campo</th><th>Descripción</th><th>Obligatorio</th></tr>
            <tr><td>Marca</td><td>Nombre de la marca (Ej: Toyota, Ford, Bosch)</td><td>Sí</td></tr>
            <tr><td>Estado</td><td>Activo / Inactivo</td><td>Sí</td></tr>
        </table>

        <div class="step">
            <strong>Paso 3:</strong> Presione <strong>"Crear"</strong>.<br>
            El sistema guardará automáticamente el usuario y la fecha de alta.
        </div>
    </div>

    <div class="section">
        <h2>4. Editar una Marca</h2>
        <div class="step">
            <strong>Paso 1:</strong> En el listado, haga clic en <strong>"Editar"</strong>.<br>
            <strong>Paso 2:</strong> Modifique los campos.<br>
            <strong>Paso 3:</strong> Presione <strong>"Guardar"</strong>.
        </div>
    </div>

    <div class="section">
        <h2>5. Eliminar una Marca</h2>
        <div class="step">
            <strong>Paso 1:</strong> Haga clic en <strong>"Eliminar"</strong>.<br>
            <strong>Paso 2:</strong> Confirme la acción.
        </div>
        <div class="note">
            <strong>Nota:</strong> No se puede eliminar una marca que tenga modelos o artículos asociados.
        </div>
    </div>

    <div class="footer">
        Sistema AltoPiston - Manual de Usuario | Generado el {{ now()->format('d/m/Y') }}
    </div>
</body>
</html>
