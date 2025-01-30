<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Articulos extends Model
{
    use HasFactory;

    protected $table = 'articulos'; //definicion de la tabla

    protected $primaryKey = 'cod_articulo'; // Clave primaria

    public $timestamps = false;

    protected $fillable = [
        //'cod_pais',
        'descripcion',
        'cod_marca',
        'cod_modelo',
        'precio',
        'cod_medida',
        'tipo_articulo',






        'usuario_alta',
        'fec_alta'

    ]; //campos para visualizar


}
