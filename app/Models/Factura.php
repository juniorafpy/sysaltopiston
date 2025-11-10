<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Factura extends Model
{
    use HasFactory;

    protected $table = 'facturas';
    protected $primaryKey = 'cod_factura';

    protected $fillable = [
        'cod_timbrado',
        'numero_factura',
        'fecha_factura',
        'cod_cliente',
        'condicion_venta',
        'cod_condicion_compra',
        'presupuesto_venta_id',
        'orden_servicio_id',
        'subtotal_gravado_10',
        'subtotal_gravado_5',
        'subtotal_exenta',
        'total_iva_10',
        'total_iva_5',
        'total_general',
        'estado',
        'observaciones'
    ];

    protected $casts = [
        'fecha_factura' => 'date',
        'subtotal_gravado_10' => 'decimal:2',
        'subtotal_gravado_5' => 'decimal:2',
        'subtotal_exenta' => 'decimal:2',
        'total_iva_10' => 'decimal:2',
        'total_iva_5' => 'decimal:2',
        'total_general' => 'decimal:2'
    ];

    /**
     * Relaciones
     */
    public function timbrado()
    {
        return $this->belongsTo(Timbrado::class, 'cod_timbrado', 'cod_timbrado');
    }

    public function cliente()
    {
        return $this->belongsTo(Personas::class, 'cod_cliente', 'cod_persona');
    }

    public function condicionCompra()
    {
        return $this->belongsTo(CondicionCompra::class, 'cod_condicion_compra', 'cod_condicion_compra');
    }

    public function detalles()
    {
        return $this->hasMany(FacturaDetalle::class, 'cod_factura', 'cod_factura');
    }

    public function presupuestoVenta()
    {
        return $this->belongsTo(PresupuestoVenta::class, 'presupuesto_venta_id', 'id');
    }

    public function ordenServicio()
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id', 'id');
    }

    public function libroIva()
    {
        return $this->hasOne(LibroIva::class, 'cod_factura', 'cod_factura');
    }

    public function saldosCuentaCorriente()
    {
        return $this->hasMany(CCSaldo::class, 'cod_factura', 'cod_factura');
    }

    public function vencimientos()
    {
        return $this->hasMany(FacturaVencimiento::class, 'cod_factura', 'cod_factura');
    }

    /**
     * Calcula los totales de la factura desde los detalles
     */
    public function calcularTotales(): array
    {
        $totales = [
            'subtotal_gravado_10' => 0,
            'subtotal_gravado_5' => 0,
            'subtotal_exenta' => 0,
            'total_iva_10' => 0,
            'total_iva_5' => 0,
            'total_general' => 0
        ];

        foreach ($this->detalles as $detalle) {
            switch ($detalle->tipo_iva) {
                case '10':
                    $totales['subtotal_gravado_10'] += $detalle->subtotal;
                    $totales['total_iva_10'] += $detalle->monto_iva;
                    break;
                case '5':
                    $totales['subtotal_gravado_5'] += $detalle->subtotal;
                    $totales['total_iva_5'] += $detalle->monto_iva;
                    break;
                case 'Exenta':
                    $totales['subtotal_exenta'] += $detalle->subtotal;
                    break;
            }
        }

        $totales['total_general'] =
            $totales['subtotal_gravado_10'] + $totales['total_iva_10'] +
            $totales['subtotal_gravado_5'] + $totales['total_iva_5'] +
            $totales['subtotal_exenta'];

        return $totales;
    }

    /**
     * Inserta el registro en el libro IVA
     */
    public function insertarLibroIva(): void
    {
        // Verificar que el cliente tenga RUC/Documento
        $rucCliente = $this->cliente->nro_documento ?? 'N/A';
        $razonSocial = $this->cliente->razon_social ?? $this->cliente->nombre_completo ?? 'Cliente sin nombre';

        LibroIva::create([
            'fecha' => $this->fecha_factura,
            'timbrado' => $this->timbrado->numero_timbrado,
            'numero_factura' => $this->numero_factura,
            'ruc_cliente' => $rucCliente,
            'razon_social' => $razonSocial,
            'gravado_10' => $this->subtotal_gravado_10,
            'iva_10' => $this->total_iva_10,
            'gravado_5' => $this->subtotal_gravado_5,
            'iva_5' => $this->total_iva_5,
            'exentas' => $this->subtotal_exenta,
            'total' => $this->total_general,
            'tipo_operacion' => 'Venta',
            'cod_factura' => $this->cod_factura
        ]);
    }

    /**
     * Inserta el saldo en cuenta corriente y genera vencimientos (solo para crédito)
     */
    public function insertarCCSaldo(): void
    {
        if ($this->condicion_venta !== 'Crédito') {
            return;
        }

        // Obtener el saldo anterior del cliente
        $saldoAnterior = CCSaldo::where('cod_cliente', $this->cod_cliente)
            ->orderBy('fecha_comprobante', 'desc')
            ->orderBy('cod_saldo', 'desc')
            ->first()
            ->saldo_actual ?? 0;

        // El nuevo saldo es el anterior más el debe de esta factura
        $nuevoSaldo = $saldoAnterior + $this->total_general;

        CCSaldo::create([
            'cod_cliente' => $this->cod_cliente,
            'tipo_comprobante' => 'Factura',
            'nro_comprobante' => $this->numero_factura,
            'fecha_comprobante' => $this->fecha_factura,
            'debe' => $this->total_general,
            'haber' => 0,
            'saldo_actual' => $nuevoSaldo,
            'descripcion' => "Factura Nro: {$this->numero_factura} - Total: Gs. " . number_format($this->total_general, 0, ',', '.'),
            'cod_factura' => $this->cod_factura,
            'usuario_alta' => auth()->id()
        ]);

        // Generar vencimientos si tiene condición de compra
        $this->generarVencimientos();
    }

    /**
     * Genera los vencimientos de la factura según la condición de compra
     */
    public function generarVencimientos(): void
    {
        if (!$this->cod_condicion_compra || $this->condicion_venta !== 'Crédito') {
            return;
        }

        $condicionCompra = $this->condicionCompra;

        if (!$condicionCompra) {
            return;
        }

        $condicionCompra = CondicionCompra::findOrFail($this->cod_condicion_compra);
        $diasCuota = $condicionCompra->dias_cuotas ?? 0;

        // Si dias_cuotas es 0, es contado, no genera vencimientos
        if ($diasCuota == 0) {
            return;
        }

        // Calcular cantidad de cuotas
        $cantidadCuotas = max(1, intval($diasCuota / 30));
        $montoPorCuota = $this->total_general / $cantidadCuotas;

        // Generar vencimientos
        for ($i = 1; $i <= $cantidadCuotas; $i++) {
            $fechaVencimiento = Carbon::parse($this->fecha_factura)->addDays($i * 30);

            FacturaVencimiento::create([
                'cod_factura' => $this->cod_factura,
                'nro_cuota' => $i,
                'fecha_vencimiento' => $fechaVencimiento,
                'monto_cuota' => round($montoPorCuota, 2),
                'monto_pagado' => 0,
                'saldo_pendiente' => round($montoPorCuota, 2),
                'estado' => 'Pendiente',
            ]);
        }
    }

    /**
     * Inserta movimiento de caja (solo para contado)
     */
    public function insertarMovimientoCaja(): void
    {
        if ($this->condicion_venta !== 'Contado') {
            return;
        }

        // Buscar una caja abierta del cajero
        $aperturaCaja = AperturaCaja::where('estado', 'Abierta')
            ->where('cod_cajero', auth()->user()->empleado->cod_empleado ?? null)
            ->first();

        if (!$aperturaCaja) {
            // No hay caja abierta, no se registra el movimiento
            return;
        }

        MovimientoCaja::create([
            'cod_apertura' => $aperturaCaja->cod_apertura,
            'tipo_movimiento' => 'Ingreso',
            'concepto' => "Factura Nro: {$this->numero_factura}",
            'monto' => $this->total_general,
            'tipo_documento' => 'Factura',
            'documento_id' => $this->cod_factura,
            'fecha_movimiento' => now(),
            'usuario_alta' => auth()->id(),
            'fecha_alta' => now(),
        ]);
    }

    /**
     * Proceso completo de generación de factura
     */
    public static function generarFactura(array $data): self
    {
        return DB::transaction(function () use ($data) {
            // 1. Obtener y validar el timbrado
            $timbrado = Timbrado::findOrFail($data['cod_timbrado']);

            if (!$timbrado->estaVigente()) {
                throw new \Exception('El timbrado no está vigente.');
            }

            // 2. Obtener el siguiente número de factura
            $numeroFactura = $timbrado->obtenerSiguienteNumero();
            $numeroFacturaCompleto = $timbrado->formatearNumeroFactura($numeroFactura);

            // 2.5. Determinar condicion_venta desde cod_condicion_compra
            $condicionVenta = 'Contado'; // Default
            if (isset($data['cod_condicion_compra'])) {
                $condicionCompra = CondicionCompra::find($data['cod_condicion_compra']);
                if ($condicionCompra) {
                    $condicionVenta = ($condicionCompra->dias_cuotas == 0) ? 'Contado' : 'Crédito';
                }
            } elseif (isset($data['condicion_venta'])) {
                // Fallback si viene condicion_venta directamente
                $condicionVenta = $data['condicion_venta'];
            }

            // 3. Crear la factura
            $factura = self::create([
                'cod_timbrado' => $data['cod_timbrado'],
                'numero_factura' => $numeroFacturaCompleto,
                'fecha_factura' => $data['fecha_factura'] ?? Carbon::now()->toDateString(),
                'cod_cliente' => $data['cod_cliente'],
                'condicion_venta' => $condicionVenta,
                'cod_condicion_compra' => $data['cod_condicion_compra'] ?? null,
                'presupuesto_venta_id' => $data['presupuesto_venta_id'] ?? null,
                'orden_servicio_id' => $data['orden_servicio_id'] ?? null,
                'subtotal_gravado_10' => $data['subtotal_gravado_10'] ?? 0,
                'subtotal_gravado_5' => $data['subtotal_gravado_5'] ?? 0,
                'subtotal_exenta' => $data['subtotal_exenta'] ?? 0,
                'total_iva_10' => $data['total_iva_10'] ?? 0,
                'total_iva_5' => $data['total_iva_5'] ?? 0,
                'total_general' => $data['total_general'] ?? 0,
                'estado' => 'Emitida',
                'observaciones' => $data['observaciones'] ?? null
            ]);

            // 4. Crear los detalles y calcular totales al mismo tiempo
            $totalesCalculados = [
                'subtotal_gravado_10' => 0,
                'subtotal_gravado_5' => 0,
                'subtotal_exenta' => 0,
                'total_iva_10' => 0,
                'total_iva_5' => 0,
                'total_general' => 0
            ];

            // DEBUG: Ver qué detalles vienen
            Log::info("Data detalles recibidos:", ['detalles' => $data['detalles'] ?? 'NO HAY DETALLES']);

            if (isset($data['detalles'])) {
                foreach ($data['detalles'] as $detalle) {
                    FacturaDetalle::create([
                        'cod_factura' => $factura->cod_factura,
                        'cod_articulo' => $detalle['cod_articulo'],
                        'descripcion' => $detalle['descripcion'],
                        'cantidad' => $detalle['cantidad'],
                        'precio_unitario' => $detalle['precio_unitario'],
                        'porcentaje_descuento' => $detalle['porcentaje_descuento'] ?? 0,
                        'monto_descuento' => $detalle['monto_descuento'] ?? 0,
                        'subtotal' => $detalle['subtotal'],
                        'tipo_iva' => $detalle['tipo_iva'],
                        'porcentaje_iva' => $detalle['porcentaje_iva'] ?? 0,
                        'monto_iva' => $detalle['monto_iva'] ?? 0,
                        'total' => $detalle['total']
                    ]);

                    // Acumular totales mientras creamos los detalles
                    $tipoIva = $detalle['tipo_iva'] ?? '10';
                    $subtotal = floatval($detalle['subtotal'] ?? 0);
                    $montoIva = floatval($detalle['monto_iva'] ?? 0);

                    Log::info("Procesando detalle - Tipo IVA: {$tipoIva}, Subtotal: {$subtotal}, Monto IVA: {$montoIva}");

                    if ($tipoIva === '10') {
                        $totalesCalculados['subtotal_gravado_10'] += $subtotal;
                        $totalesCalculados['total_iva_10'] += $montoIva;
                    } elseif ($tipoIva === '5') {
                        $totalesCalculados['subtotal_gravado_5'] += $subtotal;
                        $totalesCalculados['total_iva_5'] += $montoIva;
                    } elseif ($tipoIva === 'Exenta') {
                        $totalesCalculados['subtotal_exenta'] += $subtotal;
                    }
                }
            }

            // 5. Calcular total general
            $totalesCalculados['total_general'] =
                $totalesCalculados['subtotal_gravado_10'] + $totalesCalculados['total_iva_10'] +
                $totalesCalculados['subtotal_gravado_5'] + $totalesCalculados['total_iva_5'] +
                $totalesCalculados['subtotal_exenta'];

            Log::info("Totales antes de update:", $totalesCalculados);

            // Actualizar la factura con los totales
            $factura->update($totalesCalculados);

            Log::info("Factura después de update - Total: " . $factura->fresh()->total_general);            // 6. Insertar en libro IVA
            $factura->insertarLibroIva();

            // 7. Insertar en cuenta corriente (si es crédito)
            $factura->insertarCCSaldo();

            // 8. Insertar movimiento de caja (si es contado)
            $factura->insertarMovimientoCaja();

            // 9. Incrementar el número actual del timbrado
            $timbrado->incrementarNumeroActual();

            // 10. Si viene de presupuesto, actualizar su estado
            if ($factura->presupuesto_venta_id) {
                $factura->presupuestoVenta->update(['estado' => 'Facturado']);
            }

            return $factura->fresh(['detalles', 'timbrado', 'cliente']);
        });
    }

    /**
     * Scope para facturas de un cliente
     */
    public function scopeDeCliente($query, int $codCliente)
    {
        return $query->where('cod_cliente', $codCliente);
    }

    /**
     * Scope para facturas emitidas
     */
    public function scopeEmitidas($query)
    {
        return $query->where('estado', 'Emitida');
    }

    /**
     * Scope para facturas a crédito pendientes
     */
    public function scopeCreditoPendiente($query)
    {
        return $query->where('condicion_venta', 'Crédito')
                     ->whereIn('estado', ['Emitida', 'Pagada'])
                     ->whereHas('saldosCuentaCorriente', function($q) {
                         $q->where('saldo_actual', '>', 0);
                     });
    }

    /**
     * Calcula el saldo pendiente de la factura (total - cobros realizados)
     */
    public function getSaldoPendiente(): float
    {
        $totalFactura = floatval($this->total_general);

        // Sumar todos los montos de las cuotas cobradas de esta factura
        $totalCobrado = CobroDetalle::where('cod_factura', $this->cod_factura)
            ->sum('monto_cuota');

        return max(0, $totalFactura - $totalCobrado);
    }

    /**
     * Verifica si la factura está completamente pagada
     */
    public function estaPagada(): bool
    {
        return $this->getSaldoPendiente() <= 0;
    }

    /**
     * Obtiene los cobros realizados de esta factura
     */
    public function cobrosRealizados()
    {
        return $this->hasManyThrough(
            Cobro::class,
            CobroDetalle::class,
            'cod_factura', // FK en cobros_detalle
            'cod_cobro', // FK en cobros
            'cod_factura', // Local key en facturas
            'cod_cobro' // Local key en cobros_detalle
        );
    }
}
