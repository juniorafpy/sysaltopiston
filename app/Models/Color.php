<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    use HasFactory;

    protected $table = 'colores';
    protected $primaryKey = 'cod_color';
    public $timestamps = false;

    protected $fillable = [
        'descripcion',
        'usuario_alta',
        'fec_alta'
    ];
}
