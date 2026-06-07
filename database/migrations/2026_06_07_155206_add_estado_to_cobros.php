<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cobros', function (Blueprint $table) {
            $table->char('estado', 1)->default('C')->after('monto_total');
        });

        DB::unprepared('
            -- =============================================================
            -- Función: cobro_after_insert_update
            --   INSERT: inserta movimiento de caja (concepto sin detalles)
            --   UPDATE (C → A): inserta movimiento de caja reversión + revierte factura_vencimientos
            -- =============================================================
            CREATE OR REPLACE FUNCTION cobro_after_insert_update()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            AS $$
            DECLARE
                r_detalle RECORD;
            BEGIN
                IF TG_OP = \'INSERT\' THEN
                    INSERT INTO movimientos_caja
                        (cod_apertura, tipo_movimiento, concepto, monto, fecha_movimiento, usuario_alta, fecha_alta)
                    VALUES
                        (NEW.cod_apertura, \'Ingreso\',
                         \'Cobro N° \' || NEW.cod_cobro,
                         NEW.monto_total, NEW.fecha_alta, NEW.usuario_alta, NEW.fecha_alta);

                ELSIF TG_OP = \'UPDATE\' AND OLD.estado = \'C\' AND NEW.estado = \'A\' THEN
                    INSERT INTO movimientos_caja
                        (cod_apertura, tipo_movimiento, concepto, monto, fecha_movimiento, usuario_alta, fecha_alta)
                    VALUES
                        (OLD.cod_apertura, \'Egreso\',
                         \'Anulación Cobro N° \' || OLD.cod_cobro,
                         OLD.monto_total, NOW(), NEW.usuario_alta, NOW());

                    FOR r_detalle IN
                        SELECT cod_factura, numero_cuota, monto_cuota
                        FROM cobros_detalle
                        WHERE cod_cobro = OLD.cod_cobro
                    LOOP
                        UPDATE factura_vencimientos
                        SET monto_pagado = COALESCE(monto_pagado, 0) - r_detalle.monto_cuota,
                            saldo_pendiente = COALESCE(saldo_pendiente, 0) + r_detalle.monto_cuota
                        WHERE cod_factura = r_detalle.cod_factura
                          AND nro_cuota = r_detalle.numero_cuota;
                    END LOOP;
                END IF;

                RETURN NEW;
            END;
            $$;

            CREATE OR REPLACE TRIGGER trg_cobro_after_insert_update
                AFTER INSERT OR UPDATE OF estado ON cobros
                FOR EACH ROW
                EXECUTE FUNCTION cobro_after_insert_update();

            -- =============================================================
            -- Función y trigger: cobro_detalle_after_insert
            --   Se ejecuta al insertar cada fila en cobros_detalle
            --   Actualiza factura_vencimientos sumando monto_pagado
            --   y restando saldo_pendiente de la cuota correspondiente
            -- =============================================================
            CREATE OR REPLACE FUNCTION cobro_detalle_after_insert()
            RETURNS TRIGGER
            LANGUAGE plpgsql
            AS $$
            BEGIN
                UPDATE factura_vencimientos
                SET monto_pagado = COALESCE(monto_pagado, 0) + NEW.monto_cuota,
                    saldo_pendiente = GREATEST(monto_cuota - (COALESCE(monto_pagado, 0) + NEW.monto_cuota), 0)
                WHERE cod_factura = NEW.cod_factura
                  AND nro_cuota = NEW.numero_cuota;
                RETURN NEW;
            END;
            $$;

            CREATE OR REPLACE TRIGGER trg_cobro_detalle_after_insert
                AFTER INSERT ON cobros_detalle
                FOR EACH ROW
                EXECUTE FUNCTION cobro_detalle_after_insert();
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cobro_detalle_after_insert ON cobros_detalle');
        DB::unprepared('DROP FUNCTION IF EXISTS cobro_detalle_after_insert');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_cobro_after_insert_update ON cobros');
        DB::unprepared('DROP FUNCTION IF EXISTS cobro_after_insert_update');

        Schema::table('cobros', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }
};
