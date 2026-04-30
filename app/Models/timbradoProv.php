<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class timbradoProv extends Model
{
    use HasFactory;

    protected $table = 'timbrado_proveedor';

    protected $primaryKey = 'cod_timbrado';

    public $timestamps = false;

    protected $fillable = [
        'cod_proveedor',
        'num_timbrado',
        'fecha_inicial',
        'fec_vencimiento',
        'numero_inicial',
        'numero_final',
        'ind_activo',
        'ser_timbrado',
    ];

    protected $casts = [
        'fecha_inicial' => 'date',
        'fec_vencimiento' => 'date',
        'ind_activo' => 'boolean',
    ];

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'cod_proveedor', 'cod_proveedor');
    }
}
