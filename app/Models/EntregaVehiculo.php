<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EntregaVehiculo extends Model
{
    use HasFactory;

    protected $table = 'entrega_vehiculos';

    public $timestamps = false;

    protected $fillable = [
        'orden_servicio_id',
        'fecha_entrega',
        'persona_recibe',
        'documento_recibe',
        'kilometraje_salida',
        'observaciones',
        'recibe_titular',
        'firma',
        'usuario_alta',
        'fec_alta',
    ];

    protected $casts = [
        'fecha_entrega' => 'datetime',
        'recibe_titular' => 'boolean',
        'fec_alta' => 'datetime',
    ];

    public function ordenServicio(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class);
    }
}
