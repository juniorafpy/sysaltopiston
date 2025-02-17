<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Personas extends Model
{
    use HasFactory;


    protected $table = 'personas'; //definicion de la tabla

    protected $primaryKey = 'cod_persona'; // Clave primaria

    public $timestamps = false;

    protected $fillable =[
    'nombres', 'apellidos', 'razon_social', 'sexo', 'email', 'fec_nacimiento',
    'direccion', 'cod_estado_civil','edad',
    'cod_pais', 'cod_departamento', 'ind_activo',
    'ind_juridica', 'ind_fisica', 'usuario_alta', 'fec_alta', 'nro_documento'

    ]; //campos para visualizar


    public static function boot()
{
    parent::boot();

    static::saving(function ($model) {
        if (self::where('nro_documento', $model->nro_documento)->where('id', '!=', $model->id)->exists()) {
            throw ValidationException::withMessages(['nro_documento' => 'El número de documento ya está registrado.']);
        }
    });
}

public function getNombreCompletoAttribute(): string
{
    return "{$this->nombres} {$this->apellidos}";
}



}
