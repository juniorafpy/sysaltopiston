<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExistenciaArticulo extends Model
{
    use HasFactory;

    protected $table = 'existencia_articulo';
    // protected $primaryKey = 'cod_existencia'; // Comentado - usar 'id' por defecto
    public $timestamps = false;

    protected $fillable = [
        'cod_articulo',
        'cod_sucursal',
        'stock_actual',
        'usuario_alta',
        'fec_alta',
        'usuario_mod',
        'fec_mod',
    ];

    public function articulo(): BelongsTo
    {
        return $this->belongsTo(Articulos::class, 'cod_articulo', 'cod_articulo');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }
}
