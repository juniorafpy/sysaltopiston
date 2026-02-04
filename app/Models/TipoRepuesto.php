<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoRepuesto extends Model
{
    use HasFactory;

    protected $table = 'tipo_repuesto';

    protected $primaryKey = 'cod_tipo_repuesto';

    public $timestamps = false;

    protected $fillable = [
        'descripcion',
        'icono',
        'color',
        'activo',
        'usuario_alta',
        'fec_alta',
        'usuario_mod',
        'fec_mod',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
