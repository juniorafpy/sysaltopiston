<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaCreditoDebitoCompraDetalle extends Model
{
    use HasFactory;

    protected $table = 'nota_credito_debito_compra_detalles';
    protected $primaryKey = 'id_detalle';
    public $timestamps = false;

    protected $fillable = [
        'id_nota',
        'cod_articulo',
        'cantidad',
        'precio_unitario',
        'porcentaje_iva',
        'monto_total_linea',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'porcentaje_iva' => 'decimal:2',
        'monto_total_linea' => 'decimal:2',
    ];

    /**
     * Relaciones
     */
    public function notaCreditoDebitoCompra()
    {
        return $this->belongsTo(NotaCreditoDebitoCompra::class, 'id_nota', 'id_nota');
    }

    public function articulo()
    {
        return $this->belongsTo(Articulos::class, 'cod_articulo', 'cod_articulo');
    }

    /**
     * Accessors
     */
    public function getMontoIvaAttribute()
    {
        return $this->monto_total_linea * ($this->porcentaje_iva / 100);
    }

    public function getMontoSinIvaAttribute()
    {
        return $this->monto_total_linea - $this->monto_iva;
    }
}
