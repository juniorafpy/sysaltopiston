<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AjusteDetalle extends Model
{
    use HasFactory;

    protected $table = 'ajuste_detalle';
    public $incrementing = false;
    protected $primaryKey = 'cod_articulo';
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'tipo',
        'serie',
        'nro_ajuste',
        'cod_articulo',
        'cantidad',
    ];

    public function ajuste()
    {
        return $this->belongsTo(AjusteCabecera::class, 'nro_ajuste', 'nro_ajuste');
    }

    public function articulo()
    {
        return $this->belongsTo(Articulos::class, 'cod_articulo', 'cod_articulo');
    }
}
