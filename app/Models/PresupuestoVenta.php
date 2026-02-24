<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresupuestoVenta extends Model
{
    use HasFactory;

    protected $table = 'presupuesto_ventas';
    public $timestamps = false;

    protected $fillable = [
        'cod_cliente',
        'recepcion_vehiculo_id',
        'diagnostico_id',
        'fecha_presupuesto',
        'estado',
        'total',
        'observaciones',
        'cod_condicion',
        'cod_sucursal',
        'usuario_alta',
        'fec_alta',
    ];

    protected $casts = [
        'fecha_presupuesto' => 'date',
        'total' => 'float',
        'fec_alta' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cod_cliente', 'cod_cliente');
    }

    public function recepcionVehiculo()
    {
        return $this->belongsTo(RecepcionVehiculo::class, 'recepcion_vehiculo_id', 'id');
    }

    public function detalles()
    {
        return $this->hasMany(PresupuestoVentaDetalle::class);
    }

    public function condicion()
    {
        return $this->belongsTo(CondicionCompra::class, 'cod_condicion', 'cod_condicion');
    }

    public function diagnostico()
    {
        return $this->belongsTo(Diagnostico::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'presupuesto_venta_id', 'id');
    }

    /**
     * Obtener el cliente a través del diagnóstico y recepción de vehículo
     */
    public function clienteDesdeRecepcion()
    {
        return $this->diagnostico()?->recepcionVehiculo()?->cliente();
    }

    /**
     * Obtener el nombre completo del cliente desde la recepción de vehículo
     */
    public function getClienteNombreAttribute()
    {
        $cliente = $this->diagnostico?->recepcionVehiculo?->cliente;
        if ($cliente && $cliente->persona) {
            return trim($cliente->persona->nombres . ' ' . ($cliente->persona->apellidos ?? ''));
        }
        return 'Sin cliente';
    }

    /**
     * Rechazar atributos que no existen en la tabla
     */
    public function setAttribute($key, $value)
    {
        if (in_array($key, ['usuario_mod', 'fec_mod'])) {
            return $this;
        }
        return parent::setAttribute($key, $value);
    }

    /**
     * Evento: al guardar, calcular el total desde detalles
     */
    protected static function booted()
    {
        static::saved(function ($model) {
            // Sumar todos los totales de los detalles
            $totalDetalles = $model->detalles()
                ->sum('total');

            // Actualizar el total sin disparar eventos nuevamente
            $model->updateQuietly(['total' => $totalDetalles]);
        });
    }
}
