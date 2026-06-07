<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminar el trigger actual que escucha INSERT OR UPDATE
        DB::unprepared('DROP TRIGGER IF EXISTS trg_reserva_stock_os ON orden_servicio_detalles;');

        // Recrear el trigger para que SOLO escuche INSERT (evita doble reserva)
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
        
        // Restaurar el trigger original (aunque esto es para rollback)
        DB::unprepared('
            CREATE TRIGGER trg_reserva_stock_os
            AFTER INSERT OR UPDATE ON orden_servicio_detalles
            FOR EACH ROW
            EXECUTE FUNCTION fn_reserva_stock_os();
        ');
    }
};
