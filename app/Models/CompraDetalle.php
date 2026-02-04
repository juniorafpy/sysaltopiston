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

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'porcentaje_iva' => 'decimal:2',
        'monto_total_linea' => 'decimal:2',
    ];

    /**
     * Relaciones
     */
    public function cabecera()
    {
        return $this->belongsTo(CompraCabecera::class, 'id_compra_cabecera', 'id_compra_cabecera');
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

    /**
     * Calcula el total de la línea
     */
    public function calcularTotalLinea()
    {
        $this->monto_total_linea = $this->cantidad * $this->precio_unitario;
        return $this;
    }
}
