<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Empleados extends Model
{
    use HasFactory;

    protected $table = 'empleados';
    protected $primaryKey = 'cod_empleado';
    public $timestamps = false;

    protected $fillable = [
        'fec_alta',
        'cod_persona',
        'cod_cargo',
        'nombre',
        'email',
        'activo'
    ];

    protected $casts = [
        'fec_alta' => 'date',
        'activo' => 'boolean',
    ];

    /**
     * Relación con Persona
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Personas::class, 'cod_persona', 'cod_persona');
    }

    /**
     * Relación con Cargo
     */
    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class, 'cod_cargo', 'cod_cargo');
    }

    /**
     * Relación con Usuario (inversa)
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cod_empleado', 'cod_empleado');
    }

    /**
     * Relación con Aperturas de Caja
     */
    public function aperturasCaja(): HasMany
    {
        return $this->hasMany(AperturaCaja::class, 'cod_cajero', 'cod_empleado');
    }

    /**
     * Relación con Mecanico
     */
    public function mecanico(): HasOne
    {
        return $this->hasOne(Mecanico::class, 'cod_empleado', 'cod_empleado');
    }
}

