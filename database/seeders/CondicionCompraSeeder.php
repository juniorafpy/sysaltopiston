<?php

namespace Database\Seeders;

use App\Models\CondicionCompra;
use Illuminate\Database\Seeder;

class CondicionCompraSeeder extends Seeder
{
    public function run(): void
    {
        $condiciones = [
            ['descripcion' => 'Contado', 'dias_cuotas' => 0],
            ['descripcion' => '30 dias', 'dias_cuotas' => 30],
            ['descripcion' => '60 dias', 'dias_cuotas' => 60],
        ];

        foreach ($condiciones as $condicion) {
            CondicionCompra::firstOrCreate(
                ['descripcion' => $condicion['descripcion']],
                ['dias_cuotas' => $condicion['dias_cuotas']]
            );
        }
    }
}
