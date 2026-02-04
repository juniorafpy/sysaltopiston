<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class MedidasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $medidas = [
            // Unidades de cantidad
            'Unidad',
            'Par',
            'Juego',
            'Kit',
            'Set',
            'Docena',
            
            // Unidades de volumen
            'Litro',
            'Mililitro',
            'Galón',
            'Cuarto',
            
            // Unidades de peso
            'Kilogramo',
            'Gramo',
            'Libra',
            'Onza',
            
            // Unidades de longitud
            'Metro',
            'Centímetro',
            'Milímetro',
            'Pulgada',
            'Pie',
            
            // Unidades específicas para repuestos
            'Rollo',
            'Cartucho',
            'Bidón',
            'Lata',
            'Botella',
            'Tubo',
            'Caja',
            'Paquete',
            'Bolsa',
        ];

        $insertados = 0;
        foreach ($medidas as $medida) {
            $existe = DB::table('medidas')->where('descripcion', $medida)->exists();
            if (!$existe) {
                DB::table('medidas')->insert([
                    'descripcion' => $medida,
                ]);
                $insertados++;
            }
        }

        $this->command->info("✅ {$insertados} unidades de medida nuevas insertadas");
        $this->command->info("📏 Total de medidas en sistema: " . DB::table('medidas')->count());
    }
}
