<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Recreando tablas de cobros...\n";
echo str_repeat("=", 60) . "\n\n";

try {
    // Eliminar tablas existentes
    echo "1. Eliminando tablas existentes...\n";
    DB::statement('DROP TABLE IF EXISTS cobros_formas_pago CASCADE');
    echo "   ✓ cobros_formas_pago eliminada\n";

    DB::statement('DROP TABLE IF EXISTS cobros_detalle CASCADE');
    echo "   ✓ cobros_detalle eliminada\n";

    DB::statement('DROP TABLE IF EXISTS cobros CASCADE');
    echo "   ✓ cobros eliminada\n\n";

    // Crear tabla cobros
    echo "2. Creando tabla 'cobros'...\n";
    DB::statement("
        CREATE TABLE cobros (
            cod_cobro BIGSERIAL PRIMARY KEY,
            cod_cliente BIGINT NOT NULL,
            cod_apertura BIGINT NOT NULL,
            fecha_cobro DATE NOT NULL,
            monto_total DECIMAL(15,2) NOT NULL,
            observaciones TEXT,
            usuario_alta BIGINT NOT NULL,
            fecha_alta TIMESTAMP NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            CONSTRAINT fk_cobros_cliente FOREIGN KEY (cod_cliente)
                REFERENCES personas(cod_persona) ON DELETE RESTRICT,
            CONSTRAINT fk_cobros_apertura FOREIGN KEY (cod_apertura)
                REFERENCES aperturas_caja(cod_apertura) ON DELETE RESTRICT,
            CONSTRAINT fk_cobros_usuario FOREIGN KEY (usuario_alta)
                REFERENCES users(id) ON DELETE RESTRICT
        )
    ");
    echo "   ✓ Tabla 'cobros' creada\n\n";

    // Crear tabla cobros_detalle
    echo "3. Creando tabla 'cobros_detalle'...\n";
    DB::statement("
        CREATE TABLE cobros_detalle (
            cod_cobro_detalle BIGSERIAL PRIMARY KEY,
            cod_cobro BIGINT NOT NULL,
            cod_factura BIGINT NOT NULL,
            numero_cuota INTEGER NOT NULL,
            monto_cuota DECIMAL(15,2) NOT NULL,
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            CONSTRAINT fk_cobros_detalle_cobro FOREIGN KEY (cod_cobro)
                REFERENCES cobros(cod_cobro) ON DELETE CASCADE,
            CONSTRAINT fk_cobros_detalle_factura FOREIGN KEY (cod_factura)
                REFERENCES facturas(cod_factura) ON DELETE RESTRICT
        )
    ");
    echo "   ✓ Tabla 'cobros_detalle' creada\n\n";

    // Crear tabla cobros_formas_pago
    echo "4. Creando tabla 'cobros_formas_pago'...\n";
    DB::statement("
        CREATE TABLE cobros_formas_pago (
            cod_forma_pago BIGSERIAL PRIMARY KEY,
            cod_cobro BIGINT NOT NULL,
            tipo_transaccion VARCHAR(50) NOT NULL,
            monto DECIMAL(15,2) NOT NULL,
            cod_entidad_bancaria BIGINT,
            numero_voucher VARCHAR(100),
            numero_cheque VARCHAR(100),
            created_at TIMESTAMP NULL,
            updated_at TIMESTAMP NULL,
            CONSTRAINT fk_cobros_formas_pago_cobro FOREIGN KEY (cod_cobro)
                REFERENCES cobros(cod_cobro) ON DELETE CASCADE,
            CONSTRAINT fk_cobros_formas_pago_banco FOREIGN KEY (cod_entidad_bancaria)
                REFERENCES entidades_bancarias(cod_entidad_bancaria) ON DELETE SET NULL,
            CONSTRAINT chk_tipo_transaccion CHECK (tipo_transaccion IN (
                'efectivo', 'tarjeta_credito', 'tarjeta_debito', 'cheque', 'transferencia'
            ))
        )
    ");
    echo "   ✓ Tabla 'cobros_formas_pago' creada\n\n";

    // Crear índices
    echo "5. Creando índices...\n";
    DB::statement('CREATE INDEX idx_cobros_cliente ON cobros(cod_cliente)');
    echo "   ✓ Índice en cobros.cod_cliente\n";

    DB::statement('CREATE INDEX idx_cobros_apertura ON cobros(cod_apertura)');
    echo "   ✓ Índice en cobros.cod_apertura\n";

    DB::statement('CREATE INDEX idx_cobros_detalle_cobro ON cobros_detalle(cod_cobro)');
    echo "   ✓ Índice en cobros_detalle.cod_cobro\n";

    DB::statement('CREATE INDEX idx_cobros_detalle_factura ON cobros_detalle(cod_factura)');
    echo "   ✓ Índice en cobros_detalle.cod_factura\n";

    DB::statement('CREATE INDEX idx_cobros_formas_pago_cobro ON cobros_formas_pago(cod_cobro)');
    echo "   ✓ Índice en cobros_formas_pago.cod_cobro\n\n";

    // Verificar
    echo "6. Verificando tablas creadas...\n";
    $columns = DB::select("
        SELECT column_name, data_type
        FROM information_schema.columns
        WHERE table_name = 'cobros'
        ORDER BY ordinal_position
    ");

    echo "   Columnas en 'cobros': " . count($columns) . "\n";
    foreach ($columns as $col) {
        echo "   - {$col->column_name} ({$col->data_type})\n";
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✅ TABLAS RECREADAS EXITOSAMENTE\n";

} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
