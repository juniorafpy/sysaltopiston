<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MarcasModelosRepuestosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Marcas de repuestos automotrices
        $marcas = [
            'Bosch',
            'Denso',
            'Mann Filter',
            'Mahle',
            'NGK',
            'Champion',
            'Fram',
            'ACDelco',
            'Monroe',
            'Gates',
            'SKF',
            'Continental',
            'Brembo',
            'ATE',
            'Ferodo',
            'Castrol',
            'Mobil',
            'Shell',
            'Liqui Moly',
            'Valeo',
            'Hella',
            'Osram',
            'Philips',
            'Lucas',
            'Sachs',
            'Delphi',
            'TRW',
            'Motorcraft',
            'Pierburg',
            'Wix Filters',
        ];

        foreach ($marcas as $marca) {
            $existe = DB::table('marcas')->where('descripcion', $marca)->exists();
            if (!$existe) {
                DB::table('marcas')->insert([
                    'descripcion' => $marca,
                    'usuario_alta' => 'System',
                    'fec_alta' => now(),
                ]);
            }
        }

        $this->command->info('✅ Marcas cargadas');

        // Obtener el cod_marca de la primera marca para los modelos
        $primeraMarccod = DB::table('marcas')->first()->cod_marca ?? 1;

        // Modelos/Líneas de repuestos por categoría
        $modelos = [
            // Filtros
            'Filtro de Aceite',
            'Filtro de Aire',
            'Filtro de Combustible',
            'Filtro de Cabina',
            'Filtro Hidráulico',

            // Frenos
            'Pastillas de Freno Delanteras',
            'Pastillas de Freno Traseras',
            'Discos de Freno',
            'Tambores de Freno',
            'Líquido de Frenos DOT 3',
            'Líquido de Frenos DOT 4',

            // Motor
            'Bujías',
            'Correa de Distribución',
            'Correa Alternador',
            'Tensor de Correa',
            'Polea Tensora',
            'Bomba de Agua',
            'Termostato',
            'Radiador',
            'Manguera Superior',
            'Manguera Inferior',

            // Suspensión
            'Amortiguador Delantero',
            'Amortiguador Trasero',
            'Espiral Delantero',
            'Espiral Trasero',
            'Barra Estabilizadora',
            'Rótula Superior',
            'Rótula Inferior',
            'Terminal de Dirección',
            'Axial de Dirección',

            // Transmisión
            'Kit de Embrague',
            'Disco de Embrague',
            'Plato de Embrague',
            'Collarin de Embrague',
            'Aceite de Transmisión',

            // Eléctrico
            'Batería 12V',
            'Alternador',
            'Motor de Arranque',
            'Bobina de Encendido',
            'Cables de Bujía',
            'Lámpara H1',
            'Lámpara H4',
            'Lámpara H7',
            'Fusibles',

            // Aceites y Lubricantes
            'Aceite Motor 10W-40',
            'Aceite Motor 15W-40',
            'Aceite Motor 5W-30',
            'Aceite Motor 20W-50',
            'Refrigerante',
            'Líquido Hidráulico',
            'Grasa Multiuso',

            // Otros
            'Sensor de Oxígeno',
            'Sensor MAF',
            'Sensor MAP',
            'Sensor TPS',
            'Escobillas Limpiaparabisas',
            'Kit de Distribución',
            'Junta de Culata',
            'Juego de Juntas',
        ];

        foreach ($modelos as $modelo) {
            $existe = DB::table('st_modelos')->where('descripcion', $modelo)->exists();
            if (!$existe) {
                DB::table('st_modelos')->insert([
                    'cod_marca' => $primeraMarccod,
                    'descripcion' => $modelo,
                    'usuario_alta' => 'System',
                    'fec_alta' => now(),
                ]);
            }
        }

        $this->command->info('✅ Modelos/líneas cargados');
        $this->command->info('🎉 Total: ' . count($marcas) . ' marcas y ' . count($modelos) . ' modelos');
    }
}
