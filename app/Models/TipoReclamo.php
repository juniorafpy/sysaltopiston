<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoReclamo extends Model
{
    use HasFactory;

    protected $table = 'tipo_reclamos';
    protected $primaryKey = 'cod_tipo_reclamo';

    protected $fillable = [
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
