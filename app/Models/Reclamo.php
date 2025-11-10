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

    protected $fillable = [
        'cod_cliente',
        'orden_servicio_id',
        'cod_tipo_reclamo',
        'fecha_reclamo',
        'prioridad',
        'descripcion',
        'estado',
        'resolucion',
        'fecha_resolucion',
        'usuario_resolucion',
        'cod_sucursal',
        'usuario_alta',
        'fecha_alta',
    ];

    protected $casts = [
        'fecha_reclamo' => 'date',
        'fecha_resolucion' => 'date',
        'fecha_alta' => 'datetime',
    ];

    /**
     * Relación con el cliente (persona)
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Personas::class, 'cod_cliente', 'cod_persona');
    }

    /**
     * Relación con la orden de servicio
     */
    public function ordenServicio(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id', 'id');
    }

    /**
     * Relación con el tipo de reclamo
     */
    public function tipoReclamo(): BelongsTo
    {
        return $this->belongsTo(TipoReclamo::class, 'cod_tipo_reclamo', 'cod_tipo_reclamo');
    }

    /**
     * Relación con la sucursal
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    /**
     * Relación con el usuario que registró
     */
    public function usuarioAlta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_alta', 'id');
    }

    /**
     * Relación con el usuario que resolvió
     */
    public function usuarioResolucion(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_resolucion', 'id');
    }

    /**
     * Obtener el vehículo relacionado a través de la orden de servicio
     */
    public function getVehiculoAttribute()
    {
        return $this->ordenServicio?->recepcionVehiculo?->vehiculo;
    }

    /**
     * Obtener la matrícula del vehículo
     */
    public function getMatriculaAttribute()
    {
        return $this->vehiculo?->matricula ?? 'N/A';
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para filtrar por prioridad
     */
    public function scopePrioridad($query, $prioridad)
    {
        return $query->where('prioridad', $prioridad);
    }

    /**
     * Scope para reclamos pendientes
     */
    public function scopePendientes($query)
    {
        return $query->whereIn('estado', ['Pendiente', 'En Proceso']);
    }
}
