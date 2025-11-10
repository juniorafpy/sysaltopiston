<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Promocion extends Model
{
    use HasFactory;

    protected $table = 'promociones';

    protected $fillable = [
        'nombre',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'activo',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean',
    ];

    public function detalles(): HasMany
    {
        return $this->hasMany(PromocionDetalle::class);
    }

    /**
     * Verifica si la promoción está vigente en la fecha actual
     */
    public function isVigente(): bool
    {
        $hoy = Carbon::today();

        return $this->activo
            && $hoy->greaterThanOrEqualTo($this->fecha_inicio)
            && $hoy->lessThanOrEqualTo($this->fecha_fin);
    }

    /**
     * Obtiene el descuento vigente para un artículo específico
     *
     * @param int $articuloId
     * @return float|null Porcentaje de descuento o null si no hay promoción vigente
     */
    public static function getDescuentoVigente(int $articuloId): ?float
    {
        $hoy = Carbon::today();

        $detalle = PromocionDetalle::whereHas('promocion', function ($query) use ($hoy) {
            $query->where('activo', true)
                ->where('fecha_inicio', '<=', $hoy)
                ->where('fecha_fin', '>=', $hoy);
        })
        ->where('articulo_id', $articuloId)
        ->first();

        return $detalle?->porcentaje_descuento;
    }

    /**
     * Scope para promociones vigentes
     */
    public function scopeVigentes($query)
    {
        $hoy = Carbon::today();

        return $query->where('activo', true)
            ->where('fecha_inicio', '<=', $hoy)
            ->where('fecha_fin', '>=', $hoy);
    }
}
