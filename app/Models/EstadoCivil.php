<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EstadoCivil extends Model
{
    use HasFactory;

    protected $table = 'estado_civil'; //definicion de la tabla

    protected $primaryKey = 'cod_estado_civil'; // Clave primaria

    public $timestamps = false;

    protected $fillable =[

        'descripcion'
    ];
}
