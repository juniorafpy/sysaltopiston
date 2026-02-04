<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{
    use HasFactory;

    protected $table = 'cargos';
    protected $primaryKey = 'cod_cargo';
    public $timestamps = false;

    protected $fillable = [
        'descripcion',
        'responsabilidades',
        'salario_minimo',
        'salario_maximo',
        'area',
        'activo',
        'usuario_alta',
        'fec_alta',
        'usuario_mod',
        'fec_mod'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'salario_minimo' => 'decimal:2',
        'salario_maximo' => 'decimal:2',
    ];

    // Relación con empleados
    public function empleados()
    {
        return $this->hasMany(Empleados::class, 'cod_cargo', 'cod_cargo');
    }
}
