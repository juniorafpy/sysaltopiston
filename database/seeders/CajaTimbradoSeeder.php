<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CajaTimbrado;
use App\Models\Caja;
use App\Models\Timbrado;

class CajaTimbradoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (CajaTimbrado::count() > 0) {
            $this->command->info('❌ Ya existen asignaciones caja-timbrado. Saltando seeder...');
            return;
        }

        $this->command->info('🔗 Asignando timbrados a cajas...');

        // Obtener cajas y timbrados
        $cajas = Caja::all();
        $timbrados = Timbrado::vigentes()->get();

        if ($cajas->isEmpty()) {
            $this->command->error('❌ No hay cajas disponibles. Por favor ejecute CajaSeeder primero.');
            return;
        }

        if ($timbrados->isEmpty()) {
            $this->command->error('❌ No hay timbrados vigentes. Por favor ejecute TimbradoSeeder primero.');
            return;
        }

        // Asignar un timbrado a cada caja
        foreach ($cajas as $index => $caja) {
            $timbrado = $timbrados[$index % $timbrados->count()];

            CajaTimbrado::create([
                'cod_caja' => $caja->cod_caja,
                'cod_timbrado' => $timbrado->cod_timbrado,
                'activo' => true,
                'fecha_asignacion' => now(),
            ]);
        }

        // Resumen de asignaciones
        $this->command->info('');
        $this->command->table(
            ['Caja', 'Timbrado', 'Establecimiento', 'Punto Exp.'],
            CajaTimbrado::with(['caja', 'timbrado'])->get()->map(function ($asignacion) {
                return [
                    $asignacion->caja->descripcion,
                    $asignacion->timbrado->numero_timbrado,
                    $asignacion->timbrado->establecimiento,
                    $asignacion->timbrado->punto_expedicion,
                ];
            })->toArray()
        );

        $this->command->info('');
        $this->command->info('✅ Asignaciones caja-timbrado creadas exitosamente.');
    }
}
