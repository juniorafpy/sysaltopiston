<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnostico extends Model
{
    use HasFactory;

    protected $table = 'diagnosticos';

    protected $fillable = [
        'recepcion_vehiculo_id',
        'empleado_id',
        'fecha_diagnostico',
        'diagnostico_mecanico',
        'observaciones',
        'estado',
        'usuario_alta',
        'fec_alta',
    ];

    protected $casts = [
        'fecha_diagnostico' => 'datetime',
        'fec_alta' => 'datetime',
        'fec_mod' => 'datetime',
    ];

    public function recepcionVehiculo()
    {
        return $this->belongsTo(RecepcionVehiculo::class, 'recepcion_vehiculo_id', 'id');
    }

    public function empleado()
    {
        return $this->belongsTo(Empleados::class, 'empleado_id', 'cod_empleado');
    }
}
