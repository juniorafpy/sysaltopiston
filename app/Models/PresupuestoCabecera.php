<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresupuestoCabecera extends Model
{
    use HasFactory;

    protected $table = 'presupuesto_cabecera'; //definicion de la tabla

    protected $primaryKey = 'nro_presupuesto'; // Clave primaria

    public $timestamps = false;

    use HasFactory;

    protected $fillable = [
        'cod_proveedor',
        'fec_presupuesto',
        'usuario_alta',
        'fec_alta',
        'nro_pedido_ref',
    ]; //campos para visualizar


    public function pedido()
    {
        return $this->belongsTo(PedidoCabecera::class, 'cod_pedido', 'nro_pedido_ref');
    }

    public function detalles()
    {
        return $this->hasMany(PresupuestoDetalle::class, 'nro_presupuesto', 'nro_presupuesto');
    }

}
