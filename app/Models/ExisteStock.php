<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExisteStock extends Model
{
    use HasFactory;

    protected $table = 'existencia_articulo';

    public $timestamps = false;

    protected $fillable = [
        'cod_articulo',
        'cod_sucursal',
        'stock_actual',
        'stock_reservado',
        'stock_minimo',
        'usuario_alta',
        'fec_alta',
        'usuario_mod',
        'fec_mod',
    ];

    protected $casts = [
        'stock_actual' => 'float',
        'stock_reservado' => 'float',
        'stock_minimo' => 'float',
        'fec_alta' => 'datetime',
        'fec_mod' => 'datetime',
    ];

    // Relaciones
    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulos::class, 'cod_articulo', 'cod_articulo');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    /**
     * Calcula el stock disponible (actual - reservado)
     */
    public function getStockDisponibleAttribute(): float
    {
        return max(0, $this->stock_actual - $this->stock_reservado);
    }

    /**
     * Verifica si hay stock disponible suficiente
     */
    public function tieneStockDisponible(float $cantidad): bool
    {
        return $this->getStockDisponibleAttribute() >= $cantidad;
    }

    /**
     * Reserva stock para una orden de servicio
     * NOTA: La reserva real se maneja por TRIGGER en la BD
     * Este método solo valida disponibilidad
     */
    public function reservarStock(float $cantidad): bool
    {
        if (!$this->tieneStockDisponible($cantidad)) {
            return false;
        }

        // El trigger en la BD manejará la actualización actual
        return true;
    }

    /**
     * Libera stock reservado
     * NOTA: La liberación real se maneja por TRIGGER en la BD
     * Este método solo registra la intención
     */
    public function liberarStock(float $cantidad): bool
    {
        // El trigger en la BD manejará la actualización actual
        return true;
    }

    /**
     * Descuenta stock (facturación)
     * Reduce tanto stock_actual como stock_reservado
     */
    public function descontarStock(float $cantidad): bool
    {
        if ($this->stock_actual < $cantidad) {
            return false;
        }

        $this->stock_actual -= $cantidad;
        $this->stock_reservado = max(0, $this->stock_reservado - $cantidad);
        $this->usuario_mod = auth()->user()->name ?? 'Sistema';
        $this->fec_mod = now();

        return $this->save();
    }

    /**
     * Verifica si el stock está por debajo del mínimo
     */
    public function esBajoMinimo(): bool
    {
        return $this->stock_actual < $this->stock_minimo;
    }

    /**
     * Obtiene la cantidad faltante para llegar al stock mínimo
     */
    public function getCantidadFaltanteAttribute(): float
    {
        if (!$this->esBajoMinimo()) {
            return 0;
        }

        return $this->stock_minimo - $this->stock_actual;
    }
}
