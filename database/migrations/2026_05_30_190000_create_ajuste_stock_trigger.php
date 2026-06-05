<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('
            CREATE OR REPLACE FUNCTION fn_ajuste_stock_update()
            RETURNS TRIGGER AS $$
            DECLARE
                detalle RECORD;
                tipo_mov CHAR(1);
            BEGIN
                -- Obtener el tipo de movimiento (E=Entrada, S=Salida)
                SELECT tipo INTO tipo_mov FROM tipo_ajuste WHERE cod_tipo = NEW.tipo_ajuste;
                
                -- CONFIRMAR: P -> C
                IF OLD.estado = \'P\' AND NEW.estado = \'C\' THEN
                    FOR detalle IN 
                        SELECT cod_articulo, cantidad 
                        FROM ajuste_detalle 
                        WHERE nro_ajuste = NEW.nro_ajuste 
                          AND serie = NEW.serie 
                          AND tipo = NEW.tipo
                    LOOP
                        -- Crear existencia si no existe
                        IF NOT EXISTS (
                            SELECT 1 FROM existencia_articulo 
                            WHERE cod_articulo = detalle.cod_articulo 
                              AND cod_sucursal = NEW.cod_sucursal
                        ) THEN
                            INSERT INTO existencia_articulo (cod_articulo, cod_sucursal, stock_actual)
                            VALUES (detalle.cod_articulo, NEW.cod_sucursal, 0);
                        END IF;
                        
                        -- Actualizar stock
                        IF tipo_mov = \'E\' THEN
                            UPDATE existencia_articulo 
                            SET stock_actual = stock_actual + detalle.cantidad
                            WHERE cod_articulo = detalle.cod_articulo 
                              AND cod_sucursal = NEW.cod_sucursal;
                        ELSIF tipo_mov = \'S\' THEN
                            UPDATE existencia_articulo 
                            SET stock_actual = stock_actual - detalle.cantidad
                            WHERE cod_articulo = detalle.cod_articulo 
                              AND cod_sucursal = NEW.cod_sucursal;
                        END IF;
                    END LOOP;
                
                -- ANULAR: C -> A
                ELSIF OLD.estado = \'C\' AND NEW.estado = \'A\' THEN
                    FOR detalle IN 
                        SELECT cod_articulo, cantidad 
                        FROM ajuste_detalle 
                        WHERE nro_ajuste = NEW.nro_ajuste 
                          AND serie = NEW.serie 
                          AND tipo = NEW.tipo
                    LOOP
                        -- Revertir stock
                        IF tipo_mov = \'E\' THEN
                            UPDATE existencia_articulo 
                            SET stock_actual = stock_actual - detalle.cantidad
                            WHERE cod_articulo = detalle.cod_articulo 
                              AND cod_sucursal = NEW.cod_sucursal;
                        ELSIF tipo_mov = \'S\' THEN
                            UPDATE existencia_articulo 
                            SET stock_actual = stock_actual + detalle.cantidad
                            WHERE cod_articulo = detalle.cod_articulo 
                              AND cod_sucursal = NEW.cod_sucursal;
                        END IF;
                    END LOOP;
                END IF;
                
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_ajuste_stock_update
            AFTER UPDATE OF estado ON ajuste_cabecera
            FOR EACH ROW
            EXECUTE FUNCTION fn_ajuste_stock_update();
        ');
    }

    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS trg_ajuste_stock_update ON ajuste_cabecera;');
        DB::unprepared('DROP FUNCTION IF EXISTS fn_ajuste_stock_update;');
    }
};
