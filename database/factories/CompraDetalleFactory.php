<?php

namespace Database\Factories;

use App\Models\CompraDetalle;
use App\Models\Articulos;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompraDetalleFactory extends Factory
{
    protected $model = CompraDetalle::class;

    public function definition()
    {
        $cantidad = $this->faker->numberBetween(1, 10);
        $precio_unitario = $this->faker->randomFloat(2, 1000, 100000);

        return [
            'cod_articulo' => Articulos::inRandomOrder()->first()->cod_articulo ?? 1,
            'cantidad' => $cantidad,
            'precio_unitario' => $precio_unitario,
            'porcentaje_iva' => 10,
            'monto_total_linea' => $cantidad * $precio_unitario,
        ];
    }
}

