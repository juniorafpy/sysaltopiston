<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('
            CREATE OR REPLACE FUNCTION fn_reserva_stock_os()
            RETURNS TRIGGER AS $$
            DECLARE
                v_cod_sucursal INTEGER;
                v_estado_trabajo VARCHAR;
                v_diferencia NUMERIC;
            BEGIN
                -- Obtener datos de la cabecera de la Orden de Servicio
                SELECT cod_sucursal, estado_trabajo 
                INTO v_cod_sucursal, v_estado_trabajo
                FROM orden_servicios 
                WHERE id = NEW.orden_servicio_id;

                -- Solo ejecutar si la OS está "En Proceso"
                IF v_estado_trabajo = \'En Proceso\' THEN
                    
                    IF (TG_OP = \'INSERT\') THEN
                        -- Al insertar un detalle nuevo, reservar todo el stock
                        UPDATE existencia_articulo
                        SET 
                            stock_actual = stock_actual - NEW.cantidad,
                            stock_reservado = stock_reservado + NEW.cantidad,
                            usuario_mod = \'Sistema (Trigger OS)\',
                            fec_mod = NOW()
                        WHERE 
                            cod_articulo = NEW.cod_articulo 
                            AND cod_sucursal = v_cod_sucursal;
                            
                    ELSIF (TG_OP = \'UPDATE\') THEN
                        -- Al actualizar, ajustar la diferencia de cantidades
                        v_diferencia := NEW.cantidad - OLD.cantidad;
                        
                        UPDATE existencia_articulo
                        SET 
                            stock_actual = stock_actual - v_diferencia,
                            stock_reservado = stock_reservado + v_diferencia,
                            usuario_mod = \'Sistema (Trigger OS)\',
                            fec_mod = NOW()
                        WHERE 
                            cod_articulo = NEW.cod_articulo 
                            AND cod_sucursal = v_cod_sucursal;
                    END IF;
                    
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_reserva_stock_os
            AFTER INSERT OR UPDATE ON orden_servicio_detalles
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
