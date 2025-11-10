<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class OrdenCompraCabecera extends Model
{
    use HasFactory;

    protected $table = 'orden_compra_cabecera'; //definicion de la tabla

    protected $primaryKey = 'nro_orden_compra'; // Clave primaria

    public $timestamps = false;

    use HasFactory;

    protected $fillable = [
       'fec_orden',
       'nro_presupuesto_ref',
       'fec_entrega',
       'cod_proveedor',
       'cod_condicion_compra',
       'observacion',
       'usuario_alta',
       'fec_alta'
    ]; //campos para visualizar

     public function ordenCompraDetalles()
{
    return $this->hasMany(OrdenCompraDetalle::class, 'nro_orden_compra', 'nro_orden_compra');
}

     public function sucursale(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    public function proveedor()
{
    return $this->belongsTo(Proveedor::class, 'cod_proveedor');
}

public function condicionCompra()
{
    return $this->belongsTo(CondicionCompra::class, 'cod_condicion_compra');
}

     public function estadoRel()
    {
        return $this->belongsTo(\App\Models\Estados::class, 'estado');
    }

        protected static function booted()
    {
        static::creating(function ($model) {
            $model->usuario_alta = Auth::user()->name;
          //  dd(Auth::user('junior'));
            $model->fec_alta = now()->format('d/m/Y');
            $model->cod_sucursal = Auth::user()->cod_sucursal;
        });

        static::updating(function ($model) {
            $model->usuario_modifica = Auth::user()->username;
            $model->fec_modifica = now();
        });
    }


}
