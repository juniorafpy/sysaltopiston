<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Personas extends Model
{
    use HasFactory;


    protected $table = 'personas'; //definicion de la tabla

    protected $primaryKey = 'cod_persona'; // Clave primaria

    public $timestamps = false;

    protected $fillable =[
     'cod_persona',
     'nombres',
     'apellidos'
    ]; //campos para visualizar
}
