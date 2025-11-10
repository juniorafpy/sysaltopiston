<?php

namespace Database\Seeders;

use App\Models\Empleados;
use App\Models\Personas;
use Illuminate\Database\Seeder;

class MecanicosSeeder extends Seeder
{
    public function run(): void
    {
        $mecanicos = [
            [
                'nombres' => 'Carlos',
                'apellidos' => 'Gómez',
                'nro_documento' => '87654321',
                'email' => 'carlos.gomez@taller.com',
            ],
            [
                'nombres' => 'Miguel',
                'apellidos' => 'Ramírez',
                'nro_documento' => '12345678',
                'email' => 'miguel.ramirez@taller.com',
            ],
            [
                'nombres' => 'Roberto',
                'apellidos' => 'Silva',
                'nro_documento' => '98765432',
                'email' => 'roberto.silva@taller.com',
            ],
            [
                'nombres' => 'Fernando',
                'apellidos' => 'López',
                'nro_documento' => '45678912',
                'email' => 'fernando.lopez@taller.com',
            ],
        ];

        foreach ($mecanicos as $mecanicoData) {
            $persona = Personas::firstOrCreate(
                ['nro_documento' => $mecanicoData['nro_documento']],
                [
                    'nombres' => $mecanicoData['nombres'],
                    'apellidos' => $mecanicoData['apellidos'],
                    'email' => $mecanicoData['email'],
                    'usuario_alta' => 'seeder',
                ]
            );

            Empleados::firstOrCreate(
                ['cod_persona' => $persona->cod_persona],
                [
                    'fec_alta' => now()->toDateString(),
                    'nombre' => $persona->nombres . ' ' . $persona->apellidos,
                ]
            );
        }
    }
}
