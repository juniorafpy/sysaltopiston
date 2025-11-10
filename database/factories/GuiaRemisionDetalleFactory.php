<?php

namespace Database\Factories;

use App\Models\Articulos;
use App\Models\GuiaRemisionDetalle;
use Illuminate\Database\Eloquent\Factories\Factory;

class GuiaRemisionDetalleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GuiaRemisionDetalle::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        // Asegurarnos de que exista al menos un artículo
        $articulo = Articulos::inRandomOrder()->first() ?? Articulos::factory()->create();

        return [
            'articulo_id' => $articulo->id,
            'cantidad_recibida' => $this->faker->numberBetween(1, 20),
        ];
    }
}
