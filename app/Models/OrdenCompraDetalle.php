<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdenCompraDetalle extends Model
{
    protected $table = 'orden_compra_detalle'; //definicion de la tabla

    protected $primaryKey = 'id_detalle'; // Clave primaria

    public $timestamps = false;

    use HasFactory;


    protected $fillable = [
        'nro_orden_compra',
        'cod_articulo',
        'cantidad',
        'precio',
        'total',
        'total_iva'
    ];


 public function cabecera()
    {
        // belongsTo(Cabecera::class, fk_en_detalle, pk_en_cabecera)
        return $this->belongsTo(OrdenCompraCabecera::class, 'nro_orden_compra', 'nro_orden_compra');
    }

public function articulo()
{
    return $this->belongsTo(Articulos::class, 'cod_articulo');
}
}
