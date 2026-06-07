<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoTarjeta extends Model
{
    protected $table = 'tipo_tarjeta';
    protected $primaryKey = 'cod_tipo_tarjeta';
    public $timestamps = false;

    protected $fillable = [
        'descripcion',
        'usuario_alta',
        'fec_alta',
    ];
}
