<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedidoDetalle extends Model
{
    use HasFactory;

    protected $table = 'pedidos_detalle';

    protected $primaryKey = 'id_detalle';


    public $timestamps = false;


    protected $fillable = [
        'cod_articulo',
        'cantidad'
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoCabeceras::class, 'cod_pedido', 'cod_pedido');
    }

    public function articulos_det()
    {
        return $this->hasMany(Articulos::class, 'cod_articulo','cod_articulo'); // 'factura_id' es la clave foránea en la tabla articulos

    }

    public function articulo()
{
    return $this->belongsTo(Articulos::class, 'cod_articulo', 'cod_articulo');
}

public function pedidos()
{
    return $this->belongsTo(PedidoCabeceras::class, 'cod_pedido', 'cod_pedido');


}

public function detalles()
{
    return $this->hasMany(PedidoDetalle::class, 'cod_pedido', 'cod_pedido');
}

public function articulos()
{
    return $this->hasManyThrough(
        Articulos::class,
        PedidoDetalle::class,
        'cod_pedido', // FK en pedido_detalles
        'cod_articulo', // FK en articulos
        'cod_pedido', // Local key en pedidos
        'cod_articulo' // FK en pedido_detalles
    );
}

}
