<?php

namespace Database\Seeders;

use App\Models\GuiaRemisionCabecera;
use App\Models\GuiaRemisionDetalle;
use Illuminate\Database\Seeder;

class GuiaRemisionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Crear 15 Guías de Remisión
        GuiaRemisionCabecera::factory()
            ->count(15)
            ->has(GuiaRemisionDetalle::factory()->count(3), 'detalles') // Cada guía tendrá 3 artículos
            ->create();
    }
}
