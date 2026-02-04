<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TimbradosNotasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fechaInicio = Carbon::now()->subDays(30);
        $fechaFin = Carbon::now()->addYear();

        $timbrados = [
            [
                'numero_timbrado' => '80000001',
                'fecha_inicio_vigencia' => $fechaInicio,
                'fecha_fin_vigencia' => $fechaFin,
                'numero_inicial' => '001-001-0000001',
                'numero_final' => '001-001-9999999',
                'numero_actual' => '001-001-0000001',
                'establecimiento' => '001',
                'punto_expedicion' => '001',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'numero_timbrado' => '80000002',
                'fecha_inicio_vigencia' => $fechaInicio,
                'fecha_fin_vigencia' => $fechaFin,
                'numero_inicial' => '001-001-0000001',
                'numero_final' => '001-001-9999999',
                'numero_actual' => '001-001-0000001',
                'establecimiento' => '001',
                'punto_expedicion' => '002',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($timbrados as $index => $timbrado) {
            // Verificar si ya existe
            $existe = DB::table('timbrados')
                ->where('numero_timbrado', $timbrado['numero_timbrado'])
                ->exists();

            if (!$existe) {
                DB::table('timbrados')->insert($timbrado);
                $tipo = $index === 0 ? 'Nota de Crédito' : 'Nota de Débito';
                echo "✓ Timbrado {$timbrado['numero_timbrado']} ({$tipo}) creado\n";
            } else {
                echo "- Timbrado {$timbrado['numero_timbrado']} ya existe\n";
            }
        }
    }
}
