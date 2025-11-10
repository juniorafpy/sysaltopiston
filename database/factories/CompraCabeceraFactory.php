<?php

namespace Database\Factories;

use App\Models\CompraCabecera;
use App\Models\Proveedor;
use App\Models\Sucursal;
use App\Models\CondicionCompra;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompraCabeceraFactory extends Factory
{
    protected $model = CompraCabecera::class;

    public function definition()
    {
        return [
            'cod_sucursal' => Sucursal::inRandomOrder()->first()->cod_sucursal ?? 1,
            'fec_comprobante' => $this->faker->dateTimeBetween('-1 year', 'now'),
            'cod_proveedor' => Proveedor::inRandomOrder()->first()->cod_proveedor ?? 1,
            'tip_comprobante' => $this->faker->randomElement(['FAC', 'CON']),
            'ser_comprobante' => $this->faker->numerify('###'),
            'timbrado' => $this->faker->numerify('#####'),
            'nro_comprobante' => $this->faker->unique()->numerify('#####'),
            'cod_condicion_compra' => CondicionCompra::inRandomOrder()->first()->cod_condicion_compra ?? 1,
            'fec_vencimiento' => $this->faker->dateTimeBetween('now', '+1 year'),
            'nro_oc_ref' => null,
            'observacion' => $this->faker->sentence,
        ];
    }
}

