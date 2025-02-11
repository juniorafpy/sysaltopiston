<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    use HasFactory;

    protected $table = 'sucursal'; //definicion de la tabla

    protected $primaryKey = 'cod_sucursal'; // Clave primaria

    const UPDATED_AT = null;
    public $timestamps = false;

    protected $fillable =[
        //'cod_pais',
        'descripcion',

    ]; //campos para visualizar
}
