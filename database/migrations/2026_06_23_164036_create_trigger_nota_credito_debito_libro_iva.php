<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_insertar_libro_iva_compra_nota()
            RETURNS TRIGGER AS $$
            DECLARE
                v_cod_sucursal INTEGER;
                v_tip_comprobante VARCHAR(10);
                v_ser_comprobante VARCHAR(20);
                v_nro_comprobante VARCHAR(20);
                v_cod_proveedor INTEGER;
                v_fec_comprobante DATE;
                v_usuario_alta VARCHAR(100);
                v_usuario_id INTEGER;
                v_signo NUMERIC;
                v_iva10 NUMERIC := 0;
                v_iva5 NUMERIC := 0;
                v_exenta NUMERIC := 0;
                v_total NUMERIC := 0;
            BEGIN
                -- Datos de la cabecera de la nota
                SELECT
                    ncd.tip_comprobante,
                    ncd.ser_comprobante,
                    ncd.nro_comprobante,
                    ncd.cod_proveedor,
                    ncd.fec_comprobante,
                    ncd.usuario_alta,
                    cc.cod_sucursal
                INTO
                    v_tip_comprobante,
                    v_ser_comprobante,
                    v_nro_comprobante,
                    v_cod_proveedor,
                    v_fec_comprobante,
                    v_usuario_id,
                    v_cod_sucursal
                FROM nota_credito_debito_compras ncd
                LEFT JOIN cm_compras_cabecera cc ON cc.id_compra_cabecera = ncd.id_compra_cabecera
                WHERE ncd.id_nota = NEW.id_nota;

                -- Si no se encuentra la cabecera, no hacer nada
                IF NOT FOUND THEN
                    RETURN NEW;
                END IF;

                -- Signo segun tipo de nota
                IF v_tip_comprobante = 'NC' THEN
                    v_signo := -1;
                ELSE
                    v_signo := 1;
                END IF;

                -- Calcular totales desde los detalles de la nota
                SELECT
                    COALESCE(SUM(CASE WHEN d.porcentaje_iva = 10 THEN d.monto_total_linea ELSE 0 END) / 11, 0),
                    COALESCE(SUM(CASE WHEN d.porcentaje_iva = 5 THEN d.monto_total_linea ELSE 0 END) / 21, 0),
                    COALESCE(SUM(CASE WHEN COALESCE(d.porcentaje_iva, 0) = 0 THEN d.monto_total_linea ELSE 0 END), 0),
                    COALESCE(SUM(d.monto_total_linea), 0)
                INTO
                    v_iva10,
                    v_iva5,
                    v_exenta,
                    v_total
                FROM nota_credito_debito_compra_detalles d
                WHERE d.id_nota = NEW.id_nota;

                -- Resolver nombre del usuario
                SELECT COALESCE(name, 'Sistema') INTO v_usuario_alta
                FROM users WHERE id = v_usuario_id;

                -- Eliminar registro previo del libro IVA para esta nota (si existe)
                DELETE FROM libro_iva_compra
                WHERE tip_comprobante = v_tip_comprobante
                  AND ser_comprobante = v_ser_comprobante
                  AND nro_comprobante = v_nro_comprobante
                  AND cod_proveedor = v_cod_proveedor;

                -- Insertar en libro IVA compras
                INSERT INTO libro_iva_compra (
                    cod_sucursal,
                    nro_comprobante,
                    ser_comprobante,
                    cod_proveedor,
                    fec_comprobante,
                    iva10,
                    iva5,
                    exenta,
                    total,
                    fec_alta,
                    usuario_alta,
                    tip_comprobante
                ) VALUES (
                    v_cod_sucursal,
                    v_nro_comprobante,
                    v_ser_comprobante,
                    v_cod_proveedor,
                    v_fec_comprobante,
                    ROUND(v_iva10 * v_signo, 2),
                    ROUND(v_iva5 * v_signo, 2),
                    ROUND(v_exenta * v_signo, 2),
                    ROUND(v_total * v_signo, 2),
                    CURRENT_DATE,
                    v_usuario_alta,
                    v_tip_comprobante
                );

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        SQL);

        DB::unprepared(<<<'SQL'
            DROP TRIGGER IF EXISTS trg_insertar_libro_iva_compra_nota ON nota_credito_debito_compra_detalles;
            CREATE TRIGGER trg_insertar_libro_iva_compra_nota
            AFTER INSERT ON nota_credito_debito_compra_detalles
            FOR EACH ROW
            EXECUTE FUNCTION fn_insertar_libro_iva_compra_nota();
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_insertar_libro_iva_compra_nota ON nota_credito_debito_compra_detalles;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_insertar_libro_iva_compra_nota();');
    }
};
