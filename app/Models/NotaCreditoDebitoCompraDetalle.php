<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaCreditoDebitoCompraDetalle extends Model
{
    use HasFactory;

    protected $fillable = [
        'nota_credito_debito_compra_id',
        'articulo_id',
        'cantidad',
        'precio_unitario',
        'subtotal',
    ];

    public function notaCreditoDebitoCompra()
    {
        return $this->belongsTo(NotaCreditoDebitoCompra::class, 'nota_credito_debito_compra_id');
    }

    public function articulo()
    {
        return $this->belongsTo(Articulos::class, 'articulo_id', 'cod_articulo');
    }
}
