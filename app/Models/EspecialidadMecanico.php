<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EspecialidadMecanico extends Model
{
    use HasFactory;

    protected $table = 'especialidad_mecanico';

    protected $primaryKey = 'cod_especialidad';

    public $timestamps = false;

    protected $fillable = [
        'descripcion',
        'usuario_alta',
        'fec_alta',
    ];
}
