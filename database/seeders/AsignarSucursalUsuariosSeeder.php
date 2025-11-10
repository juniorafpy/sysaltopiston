<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class AsignarSucursalUsuariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener la primera sucursal o crear una por defecto
        $sucursal = Sucursal::first();

        if (!$sucursal) {
            $this->command->warn('⚠️  No hay sucursales. Creando sucursal por defecto...');

            $sucursal = Sucursal::create([
                'descripcion' => 'Sucursal Central',
            ]);

            $this->command->info('✅ Sucursal Central creada.');
        }

        // Obtener todos los usuarios sin sucursal asignada
        $usuarios = User::whereNull('cod_sucursal')->get();

        if ($usuarios->isEmpty()) {
            $this->command->info('ℹ️  Todos los usuarios ya tienen sucursal asignada.');
            return;
        }

        $this->command->info("📋 Asignando sucursal '{$sucursal->descripcion}' a {$usuarios->count()} usuario(s)...");

        foreach ($usuarios as $usuario) {
            $usuario->update(['cod_sucursal' => $sucursal->cod_sucursal]);
            $this->command->info("  ✅ {$usuario->name} ({$usuario->email}) → Sucursal: {$sucursal->descripcion}");
        }

        $this->command->info('🎉 Sucursales asignadas exitosamente.');
    }
}
