<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoServicio extends Model
{
    use HasFactory;

     protected $table = 'sm_tipo_servicio';

    protected $primaryKey = 'cod_tipo_servicio';

    public $timestamps = false;

    protected $fillable = [
        'descripcion',
        'usuario_alta',
        'fec_alta',
        'estado',
    ];
    
}
