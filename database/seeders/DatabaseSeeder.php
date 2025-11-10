<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

                $this->call([
            CondicionCompraSeeder::class,
            MecanicosSeeder::class,
            RecepcionVehiculoSeeder::class,
            PresupuestoVentaSeeder::class,
            CompraSeeder::class,
            ExisteStockSeeder::class, // Stock inicial para artículos

            // Módulo de Ventas
            CajaSeeder::class,
            TimbradoSeeder::class,
            CajaTimbradoSeeder::class,
            AperturaCajaSeeder::class,
            FacturaSeeder::class,
        ]);
    }
}
