<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Manual de Usuario - Países</title>
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
    <h1>Manual de Usuario - Módulo Países</h1>

    <div class="section">
        <h2>1. Descripción General</h2>
        <p>El módulo <strong>Países</strong> permite gestionar el catálogo de países disponibles en el sistema. Este referencial es utilizado en otros módulos como Clientes, Proveedores y Sucursales.</p>
    </div>

    <div class="section">
        <h2>2. Acceso al Módulo</h2>
        <div class="step">
            <strong>Paso 1:</strong> En el menú lateral, despliegue el grupo <strong>"Referenciales"</strong>.<br>
            <strong>Paso 2:</strong> Haga clic en <strong>"Países"</strong>.
        </div>
    </div>

    <div class="section">
        <h2>3. Crear un País</h2>
        <div class="step">
            <strong>Paso 1:</strong> Presione el botón <strong>"Nuevo País"</strong> (esquina superior derecha).<br>
            <strong>Paso 2:</strong> Complete los campos del formulario:
        </div>

        <table class="field-table">
            <tr><th>Campo</th><th>Descripción</th><th>Obligatorio</th></tr>
            <tr><td>Descripción</td><td>Nombre completo del país (Ej: Paraguay)</td><td>Sí</td></tr>
            <tr><td>Gentilicio</td><td>Adjetivo patrio (Ej: Paraguayo)</td><td>Sí</td></tr>
            <tr><td>Abreviatura</td><td>Código de 3 letras (Ej: PRY)</td><td>Sí</td></tr>
            <tr><td>Activo</td><td>Indica si el país está habilitado en el sistema</td><td>Sí</td></tr>
        </table>

        <div class="step">
            <strong>Paso 3:</strong> Presione <strong>"Crear"</strong> para guardar.<br>
            <strong>Paso 4:</strong> El sistema mostrará un mensaje de confirmación y redirigirá al listado.
        </div>
    </div>

    <div class="section">
        <h2>4. Editar un País</h2>
        <div class="step">
            <strong>Paso 1:</strong> En el listado, haga clic en el ícono <strong>"Editar"</strong> del país deseado.<br>
            <strong>Paso 2:</strong> Modifique los campos necesarios.<br>
            <strong>Paso 3:</strong> Presione <strong>"Guardar"</strong>.
        </div>
    </div>

    <div class="section">
        <h2>5. Eliminar un País</h2>
        <div class="step">
            <strong>Paso 1:</strong> En el listado, haga clic en el ícono <strong>"Eliminar"</strong> del país.<br>
            <strong>Paso 2:</strong> Confirme la acción en el diálogo de confirmación.
        </div>
        <div class="note">
            <strong>Nota:</strong> No podrá eliminar un país que esté siendo utilizado por clientes, proveedores u otros registros del sistema.
        </div>
    </div>

    <div class="footer">
        Sistema AltoPiston - Manual de Usuario | Generado el {{ now()->format('d/m/Y') }}
    </div>
</body>
</html>
