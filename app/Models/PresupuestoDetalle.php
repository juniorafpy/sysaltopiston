<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresupuestoDetalle extends Model
{
    use HasFactory;

    protected $table = 'presupuesto_detalle'; //definicion de la tabla

    protected $primaryKey = 'nro_presupuesto'; // Clave primaria

    public $timestamps = false;

    use HasFactory;

    protected $fillable = [
        'nro_presupuesto',
        'cod_articulo',
        'cantidad',
        'precio'
    ]; //campos para v

    public function presupuesto()
    {
        return $this->belongsTo(PresupuestoCabecera::class, 'nro_presupuesto', 'nro_presupuesto');
    }
}
