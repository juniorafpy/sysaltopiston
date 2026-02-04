<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AlmacenesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('almacenes')->insert([
            [
                'nombre' => 'Almacén Principal',
                'descripcion' => 'Almacén central de repuestos y accesorios'
            ],
            [
                'nombre' => 'Almacén Sucursal Norte',
                'descripcion' => 'Depósito de repuestos sucursal norte'
            ],
            [
                'nombre' => 'Almacén de Tránsito',
                'descripcion' => 'Almacén temporal para mercadería en tránsito'
            ],
        ]);
    }
}
