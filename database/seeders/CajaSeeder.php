<?php

namespace Database\Seeders;

use App\Models\Caja;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CajaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Verificar si ya existen cajas
        if (Caja::count() > 0) {
            $this->command->warn('⚠ Ya existen cajas registradas. Saltando seeder...');
            return;
        }

        $cajas = [
            [
                'descripcion' => 'Caja Principal',
                'cod_sucursal' => null, // Puede ser null si no hay sucursales aún
                'activo' => true,
                'usuario_alta' => 1,
                'fecha_alta' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'descripcion' => 'Caja Mostrador',
                'cod_sucursal' => null,
                'activo' => true,
                'usuario_alta' => 1,
                'fecha_alta' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'descripcion' => 'Caja Repuestos',
                'cod_sucursal' => null,
                'activo' => true,
                'usuario_alta' => 1,
                'fecha_alta' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'descripcion' => 'Caja Servicios',
                'cod_sucursal' => null,
                'activo' => true,
                'usuario_alta' => 1,
                'fecha_alta' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'descripcion' => 'Caja Express',
                'cod_sucursal' => null,
                'activo' => true,
                'usuario_alta' => 1,
                'fecha_alta' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('cajas')->insert($cajas);

        $total = Caja::count();
        $activas = Caja::where('activo', true)->count();

        $this->command->info("✓ Cajas creadas exitosamente:");
        $this->command->info("  - Total: {$total}");
        $this->command->info("  - Activas: {$activas}");
        $this->command->table(
            ['ID', 'Descripción', 'Estado'],
            Caja::all()->map(fn($c) => [
                $c->cod_caja,
                $c->descripcion,
                $c->activo ? 'Activa' : 'Inactiva'
            ])->toArray()
        );
    }
}
