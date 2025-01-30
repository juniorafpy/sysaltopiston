<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoDetalle extends Model
{
    use HasFactory;

    protected $table = 'pedidos_detalle';

    protected $fillable = [
        'cod_articulo',
        'cantidad'
    ];

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(PedidoCabecera::class, 'cod_pedido', 'cod_pedido');
    }
}
