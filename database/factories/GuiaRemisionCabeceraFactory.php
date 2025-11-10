<?php

namespace Database\Factories;

use App\Models\Almacen;
use App\Models\CompraCabecera;
use App\Models\Empleados;
use App\Models\GuiaRemisionCabecera;
use App\Models\Sucursal;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuiaRemisionCabeceraFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GuiaRemisionCabecera::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Asegurarnos de que existan sucursales, almacenes y empleados para asociar.
        $sucursal = Sucursal::first() ?? Sucursal::factory()->create();
        $almacen = Almacen::first() ?? Almacen::factory()->create();
        $empleado = Empleados::first() ?? Empleados::factory()->create();

        return [
            'compra_cabecera_id' => CompraCabecera::factory(),
            'almacen_id' => $almacen->id,
            'tipo_comprobante' => 'REM',
            'ser_remision' => 'A',
            'numero_remision' => $this->faker->unique()->numerify('#######'),
            'fecha_remision' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'cod_empleado' => $empleado->cod_empleado,
            'cod_sucursal' => $sucursal->cod_sucursal,
            'usuario_alta' => $this->faker->userName,
            'fec_alta' => now(),
            'estado' => 'P', // Pendiente
        ];
    }
}
