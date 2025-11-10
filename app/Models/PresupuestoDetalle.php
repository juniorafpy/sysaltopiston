<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresupuestoDetalle extends Model
{
    use HasFactory;

    protected $table = 'presupuesto_detalles'; //definicion de la tabla

    protected $primaryKey = 'id_detalle'; // Clave primaria
     public $incrementing = true;
       protected $keyType = 'int';

    public $timestamps = false;

    use HasFactory;

    protected $fillable = [
        'nro_presupuesto',
        'cod_articulo',
        'cantidad',
        'precio',
        'total',
        'total_iva'
    ]; //campos para v

    public function presupuestoCabecera()
{
    return $this->belongsTo(PresupuestoCabecera::class, 'nro_presupuesto', 'nro_presupuesto');
}

public function articulo()
{
    return $this->belongsTo(Articulos::class, 'cod_articulo');
}

  /*  protected static function boot()
    {
        parent::boot();

        static::saving(function ($detalle) {
            $detalle->total = $detalle->cantidad * $detalle->precio;
            $detalle->total_iva = (($detalle->cantidad * $detalle->precio) / 11) ;
        });
    }*/
}
