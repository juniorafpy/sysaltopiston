<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecepcionVehiculo extends Model
{
    use HasFactory;

    protected $table = 'recepcion_vehiculos';

    protected $fillable = [
        'cliente_id',
        'vehiculo_id',
        'fecha_recepcion',
        'kilometraje',
        'motivo_ingreso',
        'observaciones',
        'estado',
    ];

    public function cliente()
    {
        return $this->belongsTo(Personas::class, 'cliente_id', 'cod_persona');
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
