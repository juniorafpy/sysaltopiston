<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ciudad extends Model
{
    use HasFactory;

    protected $table = 'ciudad'; //definicion de la tabla

    protected $primaryKey = 'cod_ciudad'; // Clave primaria

    public $timestamps = false;

    protected $fillable =[
        'descripcion',
        'cod_departamento'
    ];
}
