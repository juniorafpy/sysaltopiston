<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoVenta extends Model
{
    use HasFactory;

    protected $table = 'tipo_venta';
    protected $primaryKey = 'cod_tipo_venta';
    public $timestamps = false;

    protected $fillable = [
        'cod_tipo_venta',
        'descripcion',
        'estado',
        'fec_alta',
        'usuario_alta',
    ];

    protected $casts = [
        'fec_alta' => 'datetime',
    ];

    public function scopeActivos($query)
    {
        return $query->where('estado', 'A');
    }
}
