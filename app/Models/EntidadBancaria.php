<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntidadBancaria extends Model
{
    use HasFactory;

    protected $table = 'entidades_bancarias';
    protected $primaryKey = 'cod_entidad_bancaria';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'ind_activo'
    ];

    /**
     * Relaciones
     */
    public function cobros()
    {
        return $this->hasMany(Cobro::class, 'cod_entidad_bancaria', 'cod_entidad_bancaria');
    }

    /**
     * Scope para entidades activas
     */
    public function scopeActivas($query)
    {
        return $query->where('ind_activo', 'S');
    }
}
