<?php

namespace Database\Seeders;

use App\Models\AperturaCaja;
use App\Models\MovimientoCaja;
use App\Models\Caja;
use App\Models\User;
use App\Models\Empleados;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AperturaCajaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cajas = Caja::all();
        $empleados = Empleados::with('persona')->get();

        if ($cajas->isEmpty() || $empleados->isEmpty()) {
            $this->command->warn('⚠ No hay cajas o empleados disponibles. Ejecute primero los seeders correspondientes.');
            return;
        }

        // Crear 10 aperturas de ejemplo (algunas cerradas, algunas abiertas)
        for ($i = 0; $i < 10; $i++) {
            $caja = $cajas->random();
            $empleado = $empleados->random();
            $fechaApertura = Carbon::now()->subDays(rand(0, 30));
            $montoInicial = rand(100000, 500000);

            // 70% cerradas, 30% abiertas
            $estaCerrada = rand(1, 10) <= 7;

            // Buscar usuario asociado al empleado
            $usuario = User::where('cod_empleado', $empleado->cod_empleado)->first();
            $usuarioId = $usuario ? $usuario->id : User::first()->id;

            $apertura = AperturaCaja::create([
                'cod_caja' => $caja->cod_caja,
                'cod_cajero' => $empleado->cod_empleado, // CORREGIDO: usar cod_empleado
                'cod_sucursal' => $caja->cod_sucursal,
                'fecha_apertura' => $fechaApertura->toDateString(),
                'hora_apertura' => $fechaApertura->toTimeString(),
                'monto_inicial' => $montoInicial,
                'observaciones_apertura' => $i % 3 == 0 ? 'Apertura normal de caja' : null,
                'estado' => $estaCerrada ? 'Cerrada' : 'Abierta',
                'usuario_alta' => $usuarioId,
                'fecha_alta' => $fechaApertura,
                'created_at' => $fechaApertura,
                'updated_at' => $fechaApertura,
            ]);

            // Generar movimientos aleatorios para esta apertura
            $cantidadMovimientos = rand(5, 20);
            $totalIngresos = 0;
            $totalEgresos = 0;

            for ($j = 0; $j < $cantidadMovimientos; $j++) {
                $esIngreso = rand(1, 10) <= 7; // 70% ingresos, 30% egresos
                $monto = rand(50000, 500000);

                if ($esIngreso) {
                    $totalIngresos += $monto;
                    $conceptos = ['Venta', 'Cobro Factura', 'Cobro OS', 'Venta Repuesto'];
                    $tiposDoc = ['Factura', 'Recibo', 'Nota Venta'];
                } else {
                    $totalEgresos += $monto;
                    $conceptos = ['Gasto', 'Compra', 'Pago Proveedor', 'Retiro'];
                    $tiposDoc = ['Recibo', 'Comprobante', null];
                }

                MovimientoCaja::create([
                    'cod_apertura' => $apertura->cod_apertura,
                    'tipo_movimiento' => $esIngreso ? 'Ingreso' : 'Egreso',
                    'concepto' => $conceptos[array_rand($conceptos)],
                    'tipo_documento' => $tiposDoc[array_rand($tiposDoc)],
                    'documento_id' => rand(1, 100),
                    'monto' => $monto,
                    'descripcion' => 'Movimiento de prueba generado automáticamente',
                    'fecha_movimiento' => $fechaApertura->addMinutes(rand(10, 480)),
                    'usuario_alta' => $usuarioId,
                    'fecha_alta' => $fechaApertura,
                    'created_at' => $fechaApertura,
                ]);
            }

            // Si está cerrada, completar datos de cierre
            if ($estaCerrada) {
                $saldoEsperado = $montoInicial + $totalIngresos - $totalEgresos;

                // 50% cuadra perfecto, 30% sobrante, 20% faltante
                $rand = rand(1, 10);
                if ($rand <= 5) {
                    $efectivoReal = $saldoEsperado; // Cuadra perfecto
                } elseif ($rand <= 8) {
                    $efectivoReal = $saldoEsperado + rand(5000, 50000); // Sobrante
                } else {
                    $efectivoReal = $saldoEsperado - rand(5000, 50000); // Faltante
                }

                $diferencia = $efectivoReal - $saldoEsperado;
                $fechaCierre = $fechaApertura->addHours(rand(8, 12));

                $apertura->update([
                    'fecha_cierre' => $fechaCierre->toDateString(),
                    'hora_cierre' => $fechaCierre->toTimeString(),
                    'efectivo_real' => $efectivoReal,
                    'saldo_esperado' => $saldoEsperado,
                    'diferencia' => $diferencia,
                    'monto_depositar' => max(0, $efectivoReal - $montoInicial),
                    'observaciones_cierre' => $diferencia != 0
                        ? ($diferencia > 0 ? 'Sobrante detectado en arqueo' : 'Faltante detectado en arqueo')
                        : 'Caja cuadrada correctamente',
                    'usuario_mod' => $usuarioId,
                    'fecha_mod' => $fechaCierre,
                    'updated_at' => $fechaCierre,
                ]);
            }
        }

        $total = AperturaCaja::count();
        $abiertas = AperturaCaja::where('estado', 'Abierta')->count();
        $cerradas = AperturaCaja::where('estado', 'Cerrada')->count();

        $this->command->info("✓ Aperturas de caja creadas: {$total}");
        $this->command->info("  - Abiertas: {$abiertas}");
        $this->command->info("  - Cerradas: {$cerradas}");
        $this->command->info("✓ Movimientos de caja creados: " . MovimientoCaja::count());
    }
}
