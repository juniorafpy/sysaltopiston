<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Impuesto extends Model
{
    use HasFactory;

    protected $table = 'impuestos';
    
    protected $primaryKey = 'cod_impuesto';
    
    protected $fillable = [
        'descripcion',
        'porcentaje',
        'activo',
    ];

    protected $casts = [
        'porcentaje' => 'decimal:2',
        'activo' => 'boolean',
    ];
}
