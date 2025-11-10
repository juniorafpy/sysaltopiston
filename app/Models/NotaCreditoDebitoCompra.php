<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaCreditoDebitoCompra extends Model
{
    use HasFactory;

    protected $fillable = [
        'compra_cabecera_id',
        'tipo_nota',
        'fecha',
        'motivo',
        'total',
    ];

    public function compraCabecera()
    {
        return $this->belongsTo(CompraCabecera::class, 'compra_cabecera_id', 'id_compra_cabecera');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'proveedor_id', 'cod_proveedor');
    }

    public function detalles()
    {
        return $this->hasMany(NotaCreditoDebitoCompraDetalle::class, 'nota_credito_debito_compra_id');
    }
}
