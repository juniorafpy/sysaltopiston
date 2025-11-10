<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompraCabecera extends Model
{

    protected $table = 'cm_compras_cabecera'; //definicion de la tabla

    protected $primaryKey = 'id_compra_cabecera'; // Clave primaria

    public $timestamps = false;

    use HasFactory;


    protected $fillable = [
'cod_sucursal',
'fec_comprobante',
'cod_proveedor',
'tip_comprobante',
'ser_comprobante',
'timbrado',
'nro_comprobante',
'cod_condicion_compra',
'fec_vencimiento',
'nro_oc_ref',
'observacion',
    ]; //campos para visualizar


    public function proveedor()
{
    return $this->belongsTo(Proveedor::class, 'cod_proveedor');
}

public function condicionCompra()
{
    return $this->belongsTo(CondicionCompra::class, 'cod_condicion_compra');
}

public function detalles()
{
    return $this->hasMany(CompraDetalle::class, 'id_compra_cabecera', 'id_compra_cabecera');
}
}
