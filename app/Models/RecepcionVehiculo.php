<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecepcionVehiculo extends Model
{
    use HasFactory;

    protected $table = 'recepcion_vehiculos';
    public $timestamps = false;

    protected $fillable = [
        'cod_cliente',
        'vehiculo_id',
        'fecha_recepcion',
        'kilometraje',
        'motivo_ingreso',
        'observaciones',
        'estado',
        'empleado_id',
       // 'inventario',
       // 'cod_sucursal',
        'usuario_alta',
        'fec_alta',
    ];

    protected $casts = [
        'inventario' => 'array',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cod_cliente', 'cod_cliente');
    }

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id', 'id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleados::class, 'empleado_id', 'cod_empleado');
    }

    public function ordenServicioDetalles()
    {
        return $this->hasMany(OrdenServicioDetalle::class, 'recepcion_vehiculo_id', 'id');
    }

    public function inventario()
    {
        return $this->hasOne(RecepcionInventario::class, 'recepcion_vehiculo_id', 'id');
    }
}
