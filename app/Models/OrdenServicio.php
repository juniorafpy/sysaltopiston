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

    protected $fillable = [
        'presupuesto_venta_id',
        'diagnostico_id',
        'recepcion_vehiculo_id',
        'cliente_id',
        'cod_sucursal',
        'fecha_inicio',
        'fecha_estimada_finalizacion',
        'fecha_finalizacion_real',
        'estado_trabajo',
        'mecanico_asignado_id',
        'observaciones_tecnicas',
        'observaciones_internas',
        'total',
        'usuario_alta',
        'fec_alta',
        'usuario_mod',
        'fec_mod',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_estimada_finalizacion' => 'date',
        'fecha_finalizacion_real' => 'date',
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
        return $this->belongsTo(Personas::class, 'cliente_id', 'cod_persona');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    public function mecanicoAsignado(): BelongsTo
    {
        return $this->belongsTo(Empleados::class, 'mecanico_asignado_id', 'cod_empleado');
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
     * Verifica si la OS puede ser editada
     */
    public function puedeEditarse(): bool
    {
        return !in_array($this->estado_trabajo, ['Finalizado', 'Cancelado', 'Facturado']);
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

        // Generar el PDF usando la vista Blade
        // Generar el PDF usando la vista Blade
                $pdf = app('dompdf.wrapper')->loadView('pdf.orden-servicio', [
                    'ordenServicio' => $this
                ]);
        // Configurar opciones del PDF
        $pdf->setPaper('letter', 'portrait');

        // Nombre del archivo
        $filename = sprintf(
            'Orden_Servicio_%d_%s.pdf',
            $this->id,
            now()->format('Ymd_His')
        );

        // Retornar según el modo
        if ($modo === 'stream') {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }
}
