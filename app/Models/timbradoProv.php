<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class timbradoProv extends Model
{

    protected $table = 'cm_timbrado_prov'; //definicion de la tabla

    protected $primaryKey = 'id_timbrado_prov'; // Clave primaria

    public $timestamps = false;
    use HasFactory;
}
