<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mecanico extends Model
{
    use HasFactory;

    protected $table = 'mecanico';
    protected $primaryKey = 'cod_mecanico';
    public $timestamps = false;

    protected $fillable = [
        'cod_empleado',
        'usuario_alta',
        'fec_alta'
    ];

    protected $casts = [
        'fec_alta' => 'datetime',
    ];

    /**
     * Relación con Empleados
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleados::class, 'cod_empleado', 'cod_empleado');
    }
}
