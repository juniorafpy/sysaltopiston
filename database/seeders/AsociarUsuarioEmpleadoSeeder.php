<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Empleados;

class AsociarUsuarioEmpleadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('=== ASOCIANDO USUARIOS CON EMPLEADOS ===');

        // Obtener todos los usuarios
        $usuarios = User::all();
        $empleados = Empleados::with('persona')->get();

        if ($usuarios->isEmpty()) {
            $this->command->error('❌ No hay usuarios en el sistema');
            return;
        }

        if ($empleados->isEmpty()) {
            $this->command->error('❌ No hay empleados en el sistema');
            return;
        }

        $this->command->info("Usuarios encontrados: {$usuarios->count()}");
        $this->command->info("Empleados encontrados: {$empleados->count()}");
        $this->command->newLine();

        // Asociar cada usuario con un empleado
        foreach ($usuarios as $index => $usuario) {
            if ($usuario->cod_empleado) {
                $empleado = Empleados::with('persona')->find($usuario->cod_empleado);
                if ($empleado) {
                    $this->command->info("✓ Usuario '{$usuario->name}' ya está asociado con empleado '{$empleado->persona->nombre_completo}'");
                }
                continue;
            }

            // Asignar empleado (round-robin si hay más usuarios que empleados)
            $empleado = $empleados[$index % $empleados->count()];

            $usuario->cod_empleado = $empleado->cod_empleado;
            $usuario->save();

            $this->command->info("✅ Usuario '{$usuario->name}' asociado con empleado '{$empleado->persona->nombre_completo}'");
        }

        $this->command->newLine();
        $this->command->info('=== PROCESO COMPLETADO ===');
    }
}
