<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuiaRemisionCabecera extends Model
{
    use HasFactory;

    protected $table = 'remision_cabecera';

    public $timestamps = false;   

    protected $fillable = [
        'compra_cabecera_id',
        'tip_factura',
        'ser_factura',
        'nro_factura',
        'cod_proveedor',
        'almacen_id',
        'tipo_comprobante',
        'ser_remision',
        'numero_remision',
        'timbrado',
        'fecha_remision',
        'cod_sucursal',
        'usuario_alta',
        'fec_alta',
        //'usuario_mod',
        //'fec_mod',
        'estado',
        'observacion',
    ];

    public function compraCabecera(): BelongsTo
    {
        return $this->belongsTo(CompraCabecera::class, 'compra_cabecera_id', 'id_compra_cabecera');
    }

    /**
     * Relación con factura usando campos compuestos (tip_factura, ser_factura, nro_factura)
     */
    public function factura()
    {
        return $this->hasOne(CompraCabecera::class, 'nro_comprobante', 'nro_factura')
            ->where('tip_comprobante', $this->tip_factura)
            ->where('ser_comprobante', $this->ser_factura);
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'cod_proveedor', 'cod_proveedor');
    }

    // almacen_id es realmente el cod_sucursal (depósito destino)
    // public function almacen(): BelongsTo
    // {
    //     return $this->belongsTo(Almacen::class, 'almacen_id');
    // }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(GuiaRemisionDetalle::class, 'guia_remision_cabecera_id');
    }
}
