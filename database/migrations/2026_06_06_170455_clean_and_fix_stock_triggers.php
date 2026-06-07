<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Eliminar TODOS los triggers existentes en la tabla para evitar duplicados
        DB::unprepared('DROP TRIGGER IF EXISTS trg_reserva_stock_os ON orden_servicio_detalles;');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_reserva_stock_os_update ON orden_servicio_detalles;');
        DB::unprepared('DROP TRIGGER IF EXISTS trg_reserva_stock_os_insert ON orden_servicio_detalles;');
        
        // 2. Eliminar la función antigua para asegurar que se use la nueva lógica
        DB::unprepared('DROP FUNCTION IF EXISTS fn_reserva_stock_os();');

        // 3. Recrear la función limpia
        DB::unprepared('
            CREATE OR REPLACE FUNCTION fn_reserva_stock_os()
            RETURNS TRIGGER AS $$
            DECLARE
                v_cod_sucursal INTEGER;
                v_estado_trabajo VARCHAR;
            BEGIN
                -- Obtener datos de la cabecera
                SELECT cod_sucursal, estado_trabajo 
                INTO v_cod_sucursal, v_estado_trabajo
                FROM orden_servicios 
                WHERE id = NEW.orden_servicio_id;

                -- Solo reservar si la OS está "En Proceso"
                IF v_estado_trabajo = \'En Proceso\' THEN
                    UPDATE existencia_articulo
                    SET 
                        stock_actual = stock_actual - NEW.cantidad,
                        stock_reservado = stock_reservado + NEW.cantidad,
                        usuario_mod = \'Sistema (Trigger OS)\',
                        fec_mod = NOW()
                    WHERE 
                        cod_articulo = NEW.cod_articulo 
                        AND cod_sucursal = v_cod_sucursal;
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;
        ');

        // 4. Crear el trigger SOLO para INSERT (evita doble ejecución en updates)
        DB::unprepared('
            CREATE TRIGGER trg_reserva_stock_os
            AFTER INSERT ON orden_servicio_detalles
            FOR EACH ROW
            EXECUTE FUNCTION fn_reserva_stock_os();
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_reserva_stock_os ON orden_servicio_detalles;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_reserva_stock_os();');
    }
};
