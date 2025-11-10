<?php

namespace Database\Seeders;

use App\Models\CompraCabecera;
use App\Models\CompraDetalle;
use Illuminate\Database\Seeder;

class CompraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CompraCabecera::factory(10)->create()->each(function ($cabecera) {
            CompraDetalle::factory(rand(1, 5))->create([
                'id_compra_cabecera' => $cabecera->id_compra_cabecera,
            ]);
        });
    }
}
