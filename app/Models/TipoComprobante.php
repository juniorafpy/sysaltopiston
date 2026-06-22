<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoComprobante extends Model
{
    use HasFactory;

    protected $table = 'tipo_comprobante';
    protected $primaryKey = 'tipo_comprobante';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'tipo_comprobante',
        'descripcion',
        'usuario_alta',
        'fec_alta',
    ];

    public function setDescripcionAttribute($value)
    {
        $this->attributes['descripcion'] = strtoupper(trim($value));
    }
}
