<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoPromocion extends Model
{
    use HasFactory;

    protected $table = 'tipo_promocion';

    protected $primaryKey = 'cod_tipo_promocion';

    public $timestamps = false;

    protected $fillable = [
        'descripcion',
        'usuario_alta',
        'fec_alta',
        'estado',
    ];
}
