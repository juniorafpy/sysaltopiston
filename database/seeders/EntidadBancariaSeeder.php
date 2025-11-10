<?php

namespace Database\Seeders;

use App\Models\EntidadBancaria;
use Illuminate\Database\Seeder;

class EntidadBancariaSeeder extends Seeder
{
    public function run(): void
    {
        $entidades = [
            ['nombre' => 'Banco Nacional de Fomento', 'abreviatura' => 'BNF'],
            ['nombre' => 'Banco Itaú Paraguay', 'abreviatura' => 'ITAÚ'],
            ['nombre' => 'Banco Continental', 'abreviatura' => 'CONTINENTAL'],
            ['nombre' => 'Banco Regional', 'abreviatura' => 'REGIONAL'],
            ['nombre' => 'Banco BASA', 'abreviatura' => 'BASA'],
            ['nombre' => 'Banco GNB Paraguay', 'abreviatura' => 'GNB'],
            ['nombre' => 'Banco Familiar', 'abreviatura' => 'FAMILIAR'],
            ['nombre' => 'Visión Banco', 'abreviatura' => 'VISIÓN'],
            ['nombre' => 'Banco Atlas', 'abreviatura' => 'ATLAS'],
            ['nombre' => 'Banco Rio', 'abreviatura' => 'RIO'],
            ['nombre' => 'Bancop', 'abreviatura' => 'BANCOP'],
            ['nombre' => 'Sudameris Bank', 'abreviatura' => 'SUDAMERIS'],
            ['nombre' => 'Solar', 'abreviatura' => 'SOLAR'],
            ['nombre' => 'Ueno Bank', 'abreviatura' => 'UENO'],
        ];

        foreach ($entidades as $entidad) {
            EntidadBancaria::firstOrCreate(
                ['nombre' => $entidad['nombre']],
                $entidad
            );
        }
    }
}
