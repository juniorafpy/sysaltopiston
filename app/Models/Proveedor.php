<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'proveedores'; //definicion de la tabla

    protected $primaryKey = 'cod_proveedor'; // Clave primaria

    public $timestamps = false;

    protected $fillable =[
        'cod_persona',
        'estado',
        'usuario_alta',
        'fec_alta',
        'usuario_mod',
        'fec_mod'
    ];



    public function personas_pro()
    {
        return $this->belongsTo(Personas::class, 'cod_persona', 'cod_persona');
    }

    public function getNombreAttribute()
    {
        if ($this->personas_pro) {
            return $this->personas_pro->razon_social ?: trim($this->personas_pro->nombres . ' ' . $this->personas_pro->apellidos);
        }
        return null;
    }

}



