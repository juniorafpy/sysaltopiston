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
    'cod_persona'
    ];



    public function personas_pro()
    {
        return $this->belongsTo(Personas::class, 'cod_persona', 'cod_persona');
    }

}



