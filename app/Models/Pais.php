<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;



class Pais extends Model
{
    use HasFactory;

    protected $table = 'pais'; //definicion de la tabla

    protected $primaryKey = 'cod_pais'; // Clave primaria

    const UPDATED_AT = null;
    public $timestamps = false;

    protected $fillable =[
        //'cod_pais',
        'descripcion',
        'gentilicio',
        'abreviatura',
        'usuario_alta',
        'fec_alta'
    
    ]; //campos para visualizar

 // Mutators para convertir a mayúsculas
 
 public function setDescripcionAttribute($value)
 {
     $this->attributes['descripcion'] = strtoupper($value);
 }

 public function setGentilicioAttribute($value)
 {
     $this->attributes['gentilicio'] = strtoupper($value);
 }

 public function setAbreviaturaAttribute($value)
 {
     $this->attributes['abreviatura'] = strtoupper($value);
 }





}


