<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuiaRemisionDetalle extends Model
{
    use HasFactory;

    protected $table = 'guia_remision_detalle';

    protected $fillable = [
        'guia_remision_cabecera_id',
        'articulo_id',
        'cantidad_recibida',
    ];

    public function cabecera(): BelongsTo
    {
        return $this->belongsTo(GuiaRemisionCabecera::class, 'guia_remision_cabecera_id');
    }

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulos::class, 'articulo_id', 'cod_articulo');
    }
}
