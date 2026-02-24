<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Reclamo extends Model
{
    use HasFactory;

    protected $table = 'reclamos';
    protected $primaryKey = 'cod_reclamo';

    public $timestamps = false;

    protected $fillable = [
        'cod_cliente',
        'orden_servicio_id',
        'cod_tipo_reclamo',
        'fecha_reclamo',
        'prioridad',
        'descripcion',
        'cod_sucursal',
        'usuario_alta',
        'fecha_alta',
    ];

    protected $casts = [
        'fecha_reclamo' => 'date',
        'fecha_alta' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Personas::class, 'cod_cliente', 'cod_persona');
    }

    public function ordenServicio(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id', 'id');
    }

    public function tipoReclamo(): BelongsTo
    {
        return $this->belongsTo(TipoReclamo::class, 'cod_tipo_reclamo', 'cod_tipo_reclamo');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    public function usuarioAlta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_alta', 'id');
    }

    public function getVehiculoAttribute()
    {
        return $this->ordenServicio?->recepcionVehiculo?->vehiculo;
    }

    public function getMatriculaAttribute()
    {
        return $this->vehiculo?->matricula ?? 'N/A';
    }

    public function scopePrioridad($query, $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }
}
