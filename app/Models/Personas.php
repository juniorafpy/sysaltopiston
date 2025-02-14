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
    'nombres', 'apellidos', 'razon_social', 'sexo', 'email', 'fec_nacimiento',
    'direccion', 'cod_estado_civil','edad',
    'cod_pais', 'cod_departamento', 'ind_activo',
    'ind_juridica', 'ind_fisica', 'usuario_alta', 'fec_alta', 'nro_documento'

    ]; //campos para visualizar
}
