<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Reclamo;
use App\Models\Personas;
use App\Models\OrdenServicio;
use App\Models\TipoReclamo;
use App\Models\User;

class ReclamoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener datos necesarios
        $clientes = Personas::limit(10)->get();
        $ordenesServicio = OrdenServicio::whereIn('estado_trabajo', ['Finalizado', 'Facturado'])->limit(15)->get();
        $tiposReclamo = TipoReclamo::all();
        $usuarios = User::all();

        if ($clientes->isEmpty()) {
            $this->command->warn('No hay clientes en la base de datos. Por favor, crea clientes primero.');
            return;
        }

        if ($ordenesServicio->isEmpty()) {
            $this->command->warn('No hay órdenes de servicio finalizadas o facturadas. Por favor, crea órdenes primero.');
            return;
        }

        if ($usuarios->isEmpty()) {
            $this->command->warn('No hay usuarios en la base de datos.');
            return;
        }

        $prioridades = ['Alta', 'Media', 'Baja'];
        $estados = ['Pendiente', 'En Proceso', 'Resuelto', 'Cerrado'];

        $descripciones = [
            'Falla de Repuesto' => [
                'El repuesto instalado (filtro de aceite) presenta fuga después de 2 días del servicio. El cliente solicita revisión urgente.',
                'La batería nueva instalada no mantiene la carga. Se descarga completamente en menos de 24 horas.',
                'Las pastillas de freno rechinán excesivamente al frenar. Cliente indica que no pasaba antes del cambio.',
                'El alternador reemplazado no está cargando correctamente la batería. Luz de batería se enciende intermitentemente.',
            ],
            'Demora en el Servicio' => [
                'El servicio de mantenimiento programado para 3 horas tomó 2 días completos. Cliente no fue notificado del retraso.',
                'La reparación del sistema de frenos lleva 5 días y aún no está completa. Se prometió entregar en 2 días.',
                'El diagnóstico del motor tomó 3 días cuando se indicó que sería en el mismo día.',
                'El cambio de aceite y filtros programado para 1 hora tomó medio día sin explicación previa.',
            ],
            'Calidad de Servicio' => [
                'Después del lavado del motor, el vehículo presenta problemas de arranque. Antes no tenía este inconveniente.',
                'El alineado y balanceado realizado no resolvió la vibración del volante. Persiste el problema original.',
                'La reparación de la transmisión no fue efectiva. Los cambios siguen siendo bruscos.',
                'El pulido de la pintura dejó marcas circulares visibles. No quedó con el acabado esperado.',
            ],
            'Atención al Cliente' => [
                'El recepcionista fue descortés y no escuchó las inquietudes del cliente sobre el vehículo.',
                'No se informó sobre el estado del servicio durante los 3 días que estuvo el vehículo en el taller.',
                'El mecánico no explicó claramente qué reparaciones se realizaron ni por qué eran necesarias.',
                'No se entregó comprobante detallado del servicio realizado. Solo una nota manuscrita.',
            ],
            'Precio/Facturación' => [
                'El presupuesto inicial fue de Gs. 500.000 pero la factura final fue de Gs. 850.000 sin explicación.',
                'Se cobraron repuestos que no fueron instalados según el cliente.',
                'La factura incluye mano de obra por trabajos que no fueron autorizados previamente.',
                'El precio cobrado no coincide con el presupuesto firmado. Hay diferencia de Gs. 300.000.',
            ],
            'Otros' => [
                'El vehículo fue rayado en el estacionamiento del taller durante el servicio.',
                'Falta el tapacubos delantero derecho después de retirar el vehículo del taller.',
                'El radio quedó desprogramado y perdió todas las estaciones guardadas.',
                'El vehículo tiene un olor extraño a quemado que no tenía antes del servicio.',
            ],
        ];

        $resoluciones = [
            'Se reemplazó el repuesto defectuoso sin costo adicional. Cliente satisfecho con la solución.',
            'Se realizó revisión completa sin cargo. Se ajustó la instalación y se verificó funcionamiento correcto.',
            'Se ofreció descuento del 30% en el próximo servicio como compensación por las molestias ocasionadas.',
            'Se devolvió el 50% del costo del servicio. Cliente aceptó la compensación.',
            'Se corrigió el trabajo sin costo adicional y se extendió la garantía por 6 meses más.',
            'Se reprogramó el servicio con prioridad y sin costo de mano de obra. Cliente conforme.',
        ];

        $this->command->info('Creando reclamos de prueba...');

        $reclamosCreados = 0;

        // Crear entre 15 y 25 reclamos
        for ($i = 0; $i < rand(15, 25); $i++) {
            $cliente = $clientes->random();
            $ordenServicio = $ordenesServicio->random();
            $tipoReclamo = $tiposReclamo->random();
            $prioridad = $prioridades[array_rand($prioridades)];
            $estado = $estados[array_rand($estados)];
            $usuario = $usuarios->random();

            // Obtener descripción según el tipo
            $descripcionesDelTipo = $descripciones[$tipoReclamo->descripcion] ?? ['Reclamo general del cliente.'];
            $descripcion = $descripcionesDelTipo[array_rand($descripcionesDelTipo)];

            // Fecha de reclamo entre 1 y 60 días atrás
            $diasAtras = rand(1, 60);
            $fechaReclamo = now()->subDays($diasAtras);

            $reclamo = Reclamo::create([
                'cod_cliente' => $cliente->cod_persona,
                'orden_servicio_id' => $ordenServicio->id,
                'cod_tipo_reclamo' => $tipoReclamo->cod_tipo_reclamo,
                'fecha_reclamo' => $fechaReclamo->format('Y-m-d'),
                'prioridad' => $prioridad,
                'descripcion' => $descripcion,
                'estado' => $estado,
                'usuario_alta' => $usuario->id,
                'fecha_alta' => $fechaReclamo,
                'cod_sucursal' => $usuario->cod_sucursal ?? null,
            ]);

            // Si el estado es Resuelto o Cerrado, agregar resolución
            if (in_array($estado, ['Resuelto', 'Cerrado'])) {
                $diasResolucion = rand(1, 7); // Resuelto entre 1 y 7 días después
                $fechaResolucion = $fechaReclamo->copy()->addDays($diasResolucion);

                $reclamo->update([
                    'resolucion' => $resoluciones[array_rand($resoluciones)],
                    'fecha_resolucion' => $fechaResolucion->format('Y-m-d'),
                    'usuario_resolucion' => $usuarios->random()->id,
                ]);
            }

            $reclamosCreados++;
        }

        $this->command->info("✓ Se crearon {$reclamosCreados} reclamos exitosamente.");

        // Mostrar estadísticas
        $this->command->info("\n--- Estadísticas de Reclamos ---");
        $this->command->info("Pendientes: " . Reclamo::where('estado', 'Pendiente')->count());
        $this->command->info("En Proceso: " . Reclamo::where('estado', 'En Proceso')->count());
        $this->command->info("Resueltos: " . Reclamo::where('estado', 'Resuelto')->count());
        $this->command->info("Cerrados: " . Reclamo::where('estado', 'Cerrado')->count());
        $this->command->info("\nPrioridad Alta: " . Reclamo::where('prioridad', 'Alta')->count());
        $this->command->info("Prioridad Media: " . Reclamo::where('prioridad', 'Media')->count());
        $this->command->info("Prioridad Baja: " . Reclamo::where('prioridad', 'Baja')->count());
    }
}
