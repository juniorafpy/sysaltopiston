<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Departamentos extends Model
{
    use HasFactory;

    protected $table = 'departamentos'; //definicion de la tabla

    protected $primaryKey = 'cod_departamento'; // Clave primaria

    public $timestamps = false;

    protected $fillable =[
        'descripcion',
        'cod_pais'
    ];
}
