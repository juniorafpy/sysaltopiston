<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromocionDetalle extends Model
{
    use HasFactory;

    protected $table = 'promocion_detalles';

    protected $fillable = [
        'promocion_id',
        'articulo_id',
        'porcentaje_descuento',
    ];

    protected $casts = [
        'porcentaje_descuento' => 'decimal:2',
    ];

    public function promocion(): BelongsTo
    {
        return $this->belongsTo(Promocion::class);
    }

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulos::class, 'articulo_id', 'cod_articulo');
    }
}
