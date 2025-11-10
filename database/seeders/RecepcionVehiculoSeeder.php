<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Marcas;
use App\Models\Modelos;
use App\Models\Personas;
use App\Models\Vehiculo;
use App\Models\Empleados;
use App\Models\RecepcionVehiculo;

class RecepcionVehiculoSeeder extends Seeder
{
    public function run(): void
    {
        // Marca y modelo
        $marca = Marcas::firstOrCreate(
            ['descripcion' => 'Toyota'],
            ['usuario_alta' => 'seeder']
        );

        $modelo = Modelos::firstOrCreate(
            ['descripcion' => 'Corolla', 'cod_marca' => $marca->cod_marca],
            ['usuario_alta' => 'seeder']
        );

        // Cliente (Persona)
        $cliente = Personas::firstOrCreate(
            ['nro_documento' => '12345678'],
            [
                'nombres' => 'Juan',
                'apellidos' => 'Pérez',
                'email' => 'juan.perez@example.com',
                'usuario_alta' => 'seeder',
            ]
        );

        // Vehículo
        $vehiculo = Vehiculo::firstOrCreate(
            ['matricula' => 'ABC-123'],
            [
                'marca_id' => $marca->cod_marca,
                'modelo_id' => $modelo->cod_modelo,
                'anio' => '2018',
                'color' => 'Blanco',
                'cliente_id' => $cliente->cod_persona,
            ]
        );

        // Empleado (mecánico) -> crear persona y empleado
        $personaEmpleado = Personas::firstOrCreate(
            ['nro_documento' => '87654321'],
            [
                'nombres' => 'Carlos',
                'apellidos' => 'Gómez',
                'email' => 'carlos.gomez@example.com',
                'usuario_alta' => 'seeder',
            ]
        );

        $empleado = Empleados::firstOrCreate(
            ['cod_persona' => $personaEmpleado->cod_persona],
            [
                'fec_alta' => now()->toDateString(),
                'nombre' => $personaEmpleado->nombres . ' ' . $personaEmpleado->apellidos,
            ]
        );

        // Recepción de vehículo
        RecepcionVehiculo::firstOrCreate(
            [
                'vehiculo_id' => $vehiculo->id,
                'cliente_id' => $cliente->cod_persona,
                'fecha_recepcion' => now(),
            ],
            [
                'kilometraje' => 50000,
                'motivo_ingreso' => 'Revisión general y ruido en motor',
                'observaciones' => 'Cliente reporta vibración al acelerar',
                'estado' => 'Ingresado',
                'empleado_id' => $empleado->cod_empleado,
            ]
        );
    }
}
