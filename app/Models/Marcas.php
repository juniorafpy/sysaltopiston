<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marcas extends Model
{
    use HasFactory;

    protected $table = 'marcas'; //definicion de la tabla

    protected $primaryKey = 'cod_marca'; // Clave primaria

    const UPDATED_AT = null;
    public $timestamps = false;

    protected $fillable =[
        //'cod_pais',
        'descripcion',
        'usuario_alta',
        'fec_alta'
    
    ]; //campos para visualizar
}
