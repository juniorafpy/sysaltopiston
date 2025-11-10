<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caja extends Model
{
    use HasFactory;

    protected $table = 'cajas';
    protected $primaryKey = 'cod_caja';

    protected $fillable = [
        'descripcion',
        'cod_sucursal',
        'activo',
        'usuario_alta',
        'fecha_alta',
        'usuario_mod',
        'fecha_mod',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_alta' => 'datetime',
        'fecha_mod' => 'datetime',
    ];

    /**
     * Relación con Sucursal
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    /**
     * Relación con Aperturas de Caja
     */
    public function aperturas(): HasMany
    {
        return $this->hasMany(AperturaCaja::class, 'cod_caja', 'cod_caja');
    }

    /**
     * Relación con Timbrados (a través de caja_timbrado)
     */
    public function timbrados(): HasMany
    {
        return $this->hasMany(CajaTimbrado::class, 'cod_caja', 'cod_caja');
    }

    /**
     * Obtener el timbrado activo de esta caja
     */
    public function timbradoActivo()
    {
        return $this->timbrados()
            ->where('activo', true)
            ->with('timbrado')
            ->first()
            ?->timbrado;
    }

    /**
     * Relación con Usuario Alta
     */
    public function usuarioAlta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_alta');
    }

    /**
     * Relación con Usuario Modificación
     */
    public function usuarioMod(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_mod');
    }

    /**
     * Scope para cajas activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para cajas por sucursal
     */
    public function scopePorSucursal($query, $codSucursal)
    {
        return $query->where('cod_sucursal', $codSucursal);
    }

    /**
     * Verificar si la caja está actualmente abierta
     */
    public function estaAbierta(): bool
    {
        return $this->aperturas()
            ->where('estado', 'Abierta')
            ->exists();
    }

    /**
     * Obtener la apertura actual (si está abierta)
     */
    public function aperturaActual()
    {
        return $this->aperturas()
            ->where('estado', 'Abierta')
            ->first();
    }
}
