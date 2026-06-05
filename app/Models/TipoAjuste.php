<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoAjuste extends Model
{
    use HasFactory;

    protected $table = 'tipo_ajuste';
    protected $primaryKey = 'cod_tipo';
    public $timestamps = false;

    protected $fillable = [
        'cod_tipo',
        'descripcion',
        'tipo',
        'usuario_alta',
        'fec_alta',
    ];
}
