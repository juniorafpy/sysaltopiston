<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CondicionCompra extends Model
{
    use HasFactory;

    protected $table = 'condicion'; //definicion de la tabla

    protected $primaryKey = 'cod_condicion'; // Clave primaria

    public $timestamps = false;

    protected $fillable =[
        'descripcion',
        'cant_cuota'
    ];
}
