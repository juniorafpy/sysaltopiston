<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cargos', function (Blueprint $table) {
            $table->id('cod_cargo');
            $table->string('descripcion', 100);
            $table->text('responsabilidades')->nullable();
            $table->decimal('salario_minimo', 10, 2)->nullable();
            $table->decimal('salario_maximo', 10, 2)->nullable();
            $table->string('area', 50)->nullable(); // Ej: Administrativa, Técnica, Ventas, etc.
            $table->boolean('activo')->default(true);
            $table->string('usuario_alta', 50)->nullable();
            $table->timestamp('fec_alta')->nullable();
            $table->string('usuario_mod', 50)->nullable();
            $table->timestamp('fec_mod')->nullable();
        });

        // Insertar cargos iniciales
        DB::table('cargos')->insert([
            // GERENCIA Y ADMINISTRACIÓN
            [
                'descripcion' => 'Gerente General',
                'responsabilidades' => 'Dirección general del taller, toma de decisiones estratégicas, supervisión de todas las áreas',
                'area' => 'Gerencia',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],
            [
                'descripcion' => 'Gerente de Servicio',
                'responsabilidades' => 'Supervisión del área técnica, control de calidad, gestión del personal técnico',
                'area' => 'Gerencia',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],
            [
                'descripcion' => 'Administrador',
                'responsabilidades' => 'Gestión administrativa, contabilidad, recursos humanos, facturación',
                'area' => 'Administrativa',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],
            [
                'descripcion' => 'Secretaria/Recepcionista',
                'responsabilidades' => 'Atención al cliente, agenda de citas, recepción de vehículos, archivo',
                'area' => 'Administrativa',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],

            // ÁREA TÉCNICA
            [
                'descripcion' => 'Jefe de Taller',
                'responsabilidades' => 'Supervisión del taller, asignación de trabajos, control de calidad técnica',
                'area' => 'Técnica',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],
            [
                'descripcion' => 'Mecánico Automotriz',
                'responsabilidades' => 'Diagnóstico y reparación de motores, sistemas mecánicos del vehículo',
                'area' => 'Técnica',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],
            [
                'descripcion' => 'Electromecánico',
                'responsabilidades' => 'Reparación de sistemas eléctricos y electrónicos del vehículo',
                'area' => 'Técnica',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],
            [
                'descripcion' => 'Técnico en Inyección Electrónica',
                'responsabilidades' => 'Diagnóstico y reparación de sistemas de inyección, escáner computarizado',
                'area' => 'Técnica',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],
            [
                'descripcion' => 'Chapista',
                'responsabilidades' => 'Reparación de carrocería, trabajos de chapa y pintura',
                'area' => 'Técnica',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],
            [
                'descripcion' => 'Pintor Automotriz',
                'responsabilidades' => 'Preparación y pintura de vehículos, acabados finales',
                'area' => 'Técnica',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],
            [
                'descripcion' => 'Gomero/Alineador',
                'responsabilidades' => 'Cambio de neumáticos, balanceo, alineación y geometría',
                'area' => 'Técnica',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],
            [
                'descripcion' => 'Auxiliar Mecánico',
                'responsabilidades' => 'Asistencia a mecánicos principales, trabajos de mantenimiento básico',
                'area' => 'Técnica',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],

            // VENTAS Y REPUESTOS
            [
                'descripcion' => 'Jefe de Repuestos',
                'responsabilidades' => 'Gestión de inventario de repuestos, compras, control de stock',
                'area' => 'Ventas',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],
            [
                'descripcion' => 'Vendedor de Repuestos',
                'responsabilidades' => 'Atención y venta de repuestos al público, asesoramiento técnico',
                'area' => 'Ventas',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],
            [
                'descripcion' => 'Asesor de Servicio',
                'responsabilidades' => 'Atención al cliente, presupuestos, seguimiento de trabajos',
                'area' => 'Ventas',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],

            // OTROS
            [
                'descripcion' => 'Encargado de Limpieza',
                'responsabilidades' => 'Mantenimiento de limpieza del taller y áreas comunes',
                'area' => 'Servicios Generales',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],
            [
                'descripcion' => 'Cajero',
                'responsabilidades' => 'Manejo de caja, cobros, arqueos diarios',
                'area' => 'Administrativa',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],
            [
                'descripcion' => 'Encargado de Almacén',
                'responsabilidades' => 'Control de inventario, recepción y despacho de repuestos',
                'area' => 'Logística',
                'activo' => true,
                'usuario_alta' => 'sistema',
                'fec_alta' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cargos');
    }
};
