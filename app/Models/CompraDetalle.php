<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompraDetalle extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'cm_compras_detalle';

    protected $primaryKey = 'id_compra_detalle';

    protected $fillable = [
        'id_compra_cabecera',
        'cod_articulo',
        'cantidad',
        'precio_unitario',
        'porcentaje_iva',
        'monto_total_linea',
    ];

    public function cabecera()
    {
        return $this->belongsTo(CompraCabecera::class, 'id_compra_cabecera', 'id_compra_cabecera');
    }

    public function articulo()
    {
        return $this->belongsTo(Articulos::class, 'cod_articulo', 'cod_articulo');
    }
}
