<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Timbrado;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TimbradoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si ya existen timbrados
        if (Timbrado::count() > 0) {
            $this->command->info('❌ Ya existen timbrados. Saltando seeder...');
            return;
        }

        $this->command->info('📄 Creando timbrados...');

        // Timbrado vigente actual
        Timbrado::create([
            'numero_timbrado' => '12345678',
            'fecha_inicio_vigencia' => Carbon::now()->subMonths(1),
            'fecha_fin_vigencia' => Carbon::now()->addMonths(5),
            'numero_inicial' => '0000001',
            'numero_final' => '0001000',
            'numero_actual' => '0000001',
            'establecimiento' => '001',
            'punto_expedicion' => '001',
            'activo' => true,
        ]);

        // Timbrado vigente para punto de expedición 002
        Timbrado::create([
            'numero_timbrado' => '87654321',
            'fecha_inicio_vigencia' => Carbon::now()->subMonths(2),
            'fecha_fin_vigencia' => Carbon::now()->addMonths(4),
            'numero_inicial' => '0000001',
            'numero_final' => '0000500',
            'numero_actual' => '0000001',
            'establecimiento' => '001',
            'punto_expedicion' => '002',
            'activo' => true,
        ]);

        // Timbrado próximo a vencer (para testing)
        Timbrado::create([
            'numero_timbrado' => '11223344',
            'fecha_inicio_vigencia' => Carbon::now()->subMonths(6),
            'fecha_fin_vigencia' => Carbon::now()->addDays(15), // Vence en 15 días
            'numero_inicial' => '0000001',
            'numero_final' => '0000200',
            'numero_actual' => '0000180', // Casi agotado
            'establecimiento' => '002',
            'punto_expedicion' => '001',
            'activo' => true,
        ]);

        // Timbrado vencido (inactivo)
        Timbrado::create([
            'numero_timbrado' => '99887766',
            'fecha_inicio_vigencia' => Carbon::now()->subMonths(12),
            'fecha_fin_vigencia' => Carbon::now()->subMonths(1), // Ya venció
            'numero_inicial' => '0000001',
            'numero_final' => '0001000',
            'numero_actual' => '0000856', // Se usaron 855
            'establecimiento' => '001',
            'punto_expedicion' => '001',
            'activo' => false, // Inactivo porque venció
        ]);

        // Timbrado futuro (aún no inicia)
        Timbrado::create([
            'numero_timbrado' => '55443322',
            'fecha_inicio_vigencia' => Carbon::now()->addMonths(6), // Inicia en 6 meses
            'fecha_fin_vigencia' => Carbon::now()->addMonths(12),
            'numero_inicial' => '0000001',
            'numero_final' => '0002000',
            'numero_actual' => '0000001',
            'establecimiento' => '001',
            'punto_expedicion' => '001',
            'activo' => true,
        ]);

        // Resumen de timbrados creados
        $this->command->info('');
        $this->command->table(
            ['Nro. Timbrado', 'Establecimiento', 'P. Exp.', 'Vigencia', 'Rango', 'Actual', 'Estado'],
            Timbrado::all()->map(function ($timbrado) {
                return [
                    $timbrado->numero_timbrado,
                    $timbrado->establecimiento,
                    $timbrado->punto_expedicion,
                    $timbrado->fecha_inicio_vigencia->format('d/m/Y') . ' - ' . $timbrado->fecha_fin_vigencia->format('d/m/Y'),
                    $timbrado->numero_inicial . ' - ' . $timbrado->numero_final,
                    $timbrado->numero_actual,
                    $timbrado->estado_vigencia,
                ];
            })->toArray()
        );

        $this->command->info('');
        $this->command->info('✅ Timbrados creados exitosamente.');
    }
}
