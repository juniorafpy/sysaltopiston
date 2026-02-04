<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PresupuestoCabecera extends Model
{
    use HasFactory;

    protected $table = 'cm_presupuesto_cabecera'; //definicion de la tabla

    protected $primaryKey = 'nro_presupuesto'; // Clave primaria

    public $timestamps = false;

    use HasFactory;

    protected $fillable = [
        'cod_proveedor',
        'fec_presupuesto',
        'usuario_alta',
        'fec_alta',
        'nro_pedido_ref',
        'cod_sucursal',
        'cod_condicion_compra',
        'estado',
        'observacion',
        'monto_gravado',
        'monto_tot_impuesto',
        'monto_general',
    ]; //campos para visualizar


   public function presupuestoDetalles()
{
    return $this->hasMany(PresupuestoDetalle::class, 'nro_presupuesto', 'nro_presupuesto');
}

public function proveedor()
{
    return $this->belongsTo(Proveedor::class, 'cod_proveedor');
}

public function sucursal()
{
    return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
}

public function condicionCompra()
{
    return $this->belongsTo(CondicionCompra::class, 'cod_condicion_compra', 'cod_condicion');
}

   public function pedido()
    {
        // belongsTo(CabeceraPedido::class, foreignKey_en_presupuesto, ownerKey_en_pedido)
        return $this->belongsTo(PedidoCabeceras::class, 'nro_pedido_ref', 'cod_pedido');
    }

        public function estadoRel()
    {
        return $this->belongsTo(\App\Models\Estados::class, 'estado');
    }


    protected static function booted()
    {
        static::creating(function ($model) {
            $model->usuario_alta = Auth::user()->username ?? Auth::user()->name;
            $model->fec_alta = now();
            $model->cod_sucursal = Auth::user()->cod_sucursal;
            // FORZAR estado PENDIENTE siempre al crear
            $model->estado = 'PENDIENTE';
        });

       /* static::updating(function ($model) {
            $model->usuario_modifica = Auth::user()->username;
            $model->fec_modifica = now();
        });*/
    }


}
