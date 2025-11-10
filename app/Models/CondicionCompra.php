<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CondicionCompra extends Model
{
    use HasFactory;

    protected $table = 'condicion_compra'; //definicion de la tabla

    protected $primaryKey = 'cod_condicion_compra'; // Clave primaria

    public $timestamps = false;

    protected $fillable =[
        'descripcion',
        'dias_cuotas'
    ];
}
