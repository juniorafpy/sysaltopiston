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
        return $this->belongsTo(PedidoCabecera::class, 'cod_pedido', 'cod_pedido');
    }

    public function articulos_det()
    {
        return $this->hasMany(Articulos::class, 'cod_articulo','cod_articulo'); // 'factura_id' es la clave foránea en la tabla articulos


    }

}
