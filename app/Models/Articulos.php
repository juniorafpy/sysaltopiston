<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Articulos extends Model implements HasMedia
{
    use HasFactory;

    protected $table = 'articulos'; //definicion de la tabla

    protected $primaryKey = 'cod_articulo'; // Clave primaria

    public $timestamps = false;

    use InteractsWithMedia;

    protected $fillable = [
        'descripcion',
        'cod_marca',
        'cod_modelo',
        'precio',
        'cod_medida',
        'cod_tip_articulo',
        'activo',
        'costo',
        'usuario_alta',
        'fec_alta',
        'usuario_mod',
        'fec_mod',
        'image',
    ];

    protected $casts = [
        'precio' => 'float',
        'costo' => 'float',
    ];

    /**
     * Relación con existe_stock (un artículo tiene stock en múltiples sucursales)
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(ExisteStock::class, 'cod_articulo', 'cod_articulo');
    }

    /**
     * Obtiene el registro de stock para una sucursal específica
     */
    public function getStockEnSucursal($codSucursal): ?ExisteStock
    {
        return $this->stocks()->where('cod_sucursal', $codSucursal)->first();
    }

    /**
     * Obtiene el stock disponible en una sucursal
     */
    public function getStockDisponibleEnSucursal($codSucursal): float
    {
        $stock = $this->getStockEnSucursal($codSucursal);
        return $stock ? $stock->stock_disponible : 0;
    }

    /**
     * Verifica si hay stock disponible suficiente en una sucursal
     */
    public function tieneStockDisponible(float $cantidad, $codSucursal): bool
    {
        return $this->getStockDisponibleEnSucursal($codSucursal) >= $cantidad;
    }

    /**
     * Reserva stock en una sucursal específica
     */
    public function reservarStock(float $cantidad, $codSucursal): bool
    {
        $stock = $this->getStockEnSucursal($codSucursal);

        if (!$stock) {
            return false; // No existe registro de stock para esta sucursal
        }

        return $stock->reservarStock($cantidad);
    }

    /**
     * Libera stock reservado en una sucursal
     */
    public function liberarStock(float $cantidad, $codSucursal): bool
    {
        $stock = $this->getStockEnSucursal($codSucursal);

        if (!$stock) {
            return false;
        }

        return $stock->liberarStock($cantidad);
    }

    /**
     * Descuenta stock en una sucursal (facturación)
     */
    public function descontarStock(float $cantidad, $codSucursal): bool
    {
        $stock = $this->getStockEnSucursal($codSucursal);

        if (!$stock) {
            return false;
        }

        return $stock->descontarStock($cantidad);
    }

    /**
     * Obtiene el stock total de todas las sucursales
     */
    public function getStockTotalAttribute(): float
    {
        return $this->stocks()->sum('stock_actual');
    }

    /**
     * Obtiene el stock reservado total de todas las sucursales
     */
    public function getStockReservadoTotalAttribute(): float
    {
        return $this->stocks()->sum('stock_reservado');
    }

    /**
     * Obtiene el stock disponible total de todas las sucursales
     */
    public function getStockDisponibleTotalAttribute(): float
    {
        return $this->stock_total - $this->stock_reservado_total;
    }


    /**
     * Relación con Marcas
     */
    public function marcas_ar(): BelongsTo
    {
        return $this->belongsTo(Marcas::class, 'cod_marca', 'cod_marca');
    }

    /**
     * Relación con Modelos
     */
    public function modelos_ar(): BelongsTo
    {
        return $this->belongsTo(Modelos::class, 'cod_modelo', 'cod_modelo');
    }

    /**
     * Relación con Medidas
     */
    public function medida_ar(): BelongsTo
    {
        return $this->belongsTo(Medidas::class, 'cod_medida', 'cod_medida');
    }

    /**
     * Relación con Tipo de Artículos
     */
    public function tipo_articulo_ar(): BelongsTo
    {
        return $this->belongsTo(TipoArticulos::class, 'cod_tip_articulo', 'cod_tip_articulo');
    }

        public function detalle_art()
    {
        return $this->belongsTo(PedidoDetalle::class, 'cod_articulo','cod_pedido'); // 'factura_id' es la clave foránea en la tabla articulos
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('imagenes') // Define la colección "imagenes"
            ->useDisk('public') // Puedes cambiar el disco si lo deseas
            ->singleFile(); // Opcional: si solo quieres una imagen por artículo
    }
}


