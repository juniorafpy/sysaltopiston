<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdenServicio extends Model
{
    use HasFactory;

    protected $table = 'orden_servicios';
    public $timestamps = false;


    protected $fillable = [
        'presupuesto_venta_id',
        'diagnostico_id',
        'recepcion_vehiculo_id',
        'cod_cliente',
        'cod_sucursal',
        'fecha_inicio',
        'fecha_estimada_finalizacion',
        //'fecha_finalizacion_real',
        'estado_trabajo',
        'cod_mecanico',
        'observaciones_tecnicas',
        'observaciones_internas',
        'total',
        'usuario_alta',
        'fec_alta',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_estimada_finalizacion' => 'date',
        //'fecha_finalizacion_real' => 'date',
        'total' => 'float',
        'fec_alta' => 'datetime',
        'fec_mod' => 'datetime',
    ];

    // Relaciones
    public function presupuestoVenta(): BelongsTo
    {
        return $this->belongsTo(PresupuestoVenta::class);
    }

    public function diagnostico(): BelongsTo
    {
        return $this->belongsTo(Diagnostico::class);
    }

    public function recepcionVehiculo(): BelongsTo
    {
        return $this->belongsTo(RecepcionVehiculo::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cod_cliente', 'cod_cliente');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    public function mecanicoAsignado(): BelongsTo
    {
        return $this->belongsTo(Empleados::class, 'cod_mecanico', 'cod_empleado');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(OrdenServicioDetalle::class);
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'orden_servicio_id', 'id');
    }

    /**
     * Protege campos que no deben editarse
     */
    public function setAttribute($key, $value)
    {
        // Rechazar intentos de establecer campos que no existen en la DB
        if (in_array($key, ['usuario_mod', 'fec_mod'])) {
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Copia los detalles del presupuesto a la orden de servicio (sin duplicar)
     */
    public function copiarDetallesDelPresupuesto(): void
    {
        if (!$this->presupuesto_venta_id) {
            return;
        }

        // Obtener el presupuesto específico
        $presupuesto = PresupuestoVenta::find($this->presupuesto_venta_id);

        if (!$presupuesto) {
            return;
        }

        // Obtener detalles ya copiados de este presupuesto
        $detallesYaCopiados = OrdenServicioDetalle::where('orden_servicio_id', $this->id)
            ->where('presupuesto_venta_detalle_id', '!=', null)
            ->pluck('presupuesto_venta_detalle_id')
            ->toArray();

        // Copiar solo los detalles que no estén ya copiados
        foreach ($presupuesto->detalles as $detallePresupuesto) {
            // Si este detalle ya fue copiado, saltar
            if (in_array($detallePresupuesto->id, $detallesYaCopiados)) {
                continue;
            }

            if (empty($detallePresupuesto->cod_articulo) || floatval($detallePresupuesto->cantidad ?? 0) <= 0) {
                continue;
            }

            OrdenServicioDetalle::create([
                'orden_servicio_id' => $this->id,
                'presupuesto_venta_detalle_id' => $detallePresupuesto->id,
                'cod_articulo' => $detallePresupuesto->cod_articulo,
                'descripcion' => $detallePresupuesto->descripcion ?? $detallePresupuesto->articulo?->descripcion,
                'cantidad' => $detallePresupuesto->cantidad,
                'cantidad_utilizada' => 0,
                'precio_unitario' => $detallePresupuesto->precio_unitario,
                'porcentaje_descuento' => $detallePresupuesto->porcentaje_descuento ?? 0,
                'monto_descuento' => $detallePresupuesto->monto_descuento ?? 0,
                'porcentaje_impuesto' => $detallePresupuesto->porcentaje_impuesto ?? 10,
                'monto_impuesto' => $detallePresupuesto->monto_impuesto,
                'subtotal' => $detallePresupuesto->subtotal,
                'total' => $detallePresupuesto->total,
                'usuario_alta' => auth()->user()->name ?? 'Sistema',
                'fec_alta' => now(),
            ]);
        }
    }

    /**
     * Verifica si la OS puede ser editada
     */
    public function puedeEditarse(): bool
    {
        return $this->estado_trabajo !== 'Finalizado';
    }

    /**
     * Reserva el stock de todos los artículos
     * Retorna un array con el resultado: ['success' => bool, 'messages' => array]
     */
    public function reservarStock(): array
    {
        if (!$this->cod_sucursal) {
            return [
                'success' => false,
                'messages' => ['No hay sucursal asignada a la orden de servicio.']
            ];
        }

        $messages = [];
        $todosReservados = true;

        foreach ($this->detalles as $detalle) {
            $resultado = $detalle->reservarStock();

            if (!$resultado) {
                $todosReservados = false;
                $articulo = $detalle->articulo;
                $stockDisponible = $articulo ? $articulo->getStockDisponibleEnSucursal($this->cod_sucursal) : 0;

                $messages[] = sprintf(
                    '❌ %s: Solicitado %.2f, Disponible %.2f',
                    $detalle->descripcion,
                    $detalle->cantidad,
                    $stockDisponible
                );
            } else {
                $messages[] = sprintf(
                    '✅ %s: %.2f unidades reservadas',
                    $detalle->descripcion,
                    $detalle->cantidad
                );
            }
        }

        return [
            'success' => $todosReservados,
            'messages' => $messages
        ];
    }

    /**
     * Libera el stock reservado de todos los artículos
     */
    public function liberarStock(): void
    {
        foreach ($this->detalles as $detalle) {
            $detalle->liberarStock();
        }
    }

    /**
     * Descuenta el stock al facturar
     */
    public function descontarStock(): bool
    {
        foreach ($this->detalles as $detalle) {
            if (!$detalle->descontarStock()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Genera el PDF de la orden de servicio
     *
     * @param string $modo 'download' para descargar, 'stream' para visualizar en navegador
     * @return \Illuminate\Http\Response
     */
    public function generarPDF(string $modo = 'download')
    {
        // Cargar todas las relaciones necesarias
        $this->load([
            'cliente',
            'presupuestoVenta',
            'diagnostico',
            'recepcionVehiculo.vehiculo.marca',
            'recepcionVehiculo.vehiculo.modelo',
            'sucursal',
            'mecanicoAsignado.persona',
            'detalles.articulo'
        ]);

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $html = view('pdf.orden-servicio', [
            'ordenServicio' => $this,
        ])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        // Nombre del archivo
        $filename = sprintf(
            'Orden_Servicio_%d_%s.pdf',
            $this->id,
            now()->format('Ymd_His')
        );

        // Retornar según el modo
        if ($modo === 'stream') {
            return response($dompdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
        }

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
