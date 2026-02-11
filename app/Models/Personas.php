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
        // Use the model's primary key name instead of hard-coded 'id',
        // because this model uses 'cod_persona' as primary key.
        $keyName = $model->getKeyName();
        $keyValue = $model->{$keyName} ?? null;

        $query = self::where('nro_documento', $model->nro_documento);
        if (!is_null($keyValue)) {
            $query->where($keyName, '!=', $keyValue);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages(['nro_documento' => 'El número de documento ya está registrado.']);
        }
    });
}

public function getNombreCompletoAttribute(): string
{
    return "{$this->nombres}, {$this->apellidos}";
}

public function facturas()
{
    return $this->hasMany(Factura::class, 'cod_cliente', 'cod_persona');
}

public function estadoCivil()
{
    return $this->belongsTo(EstadoCivil::class, 'cod_estado_civil', 'cod_estado_civil');
}

public function pais()
{
    return $this->belongsTo(Pais::class, 'cod_pais', 'cod_pais');
}

public function departamento()
{
    return $this->belongsTo(Departamentos::class, 'cod_departamento', 'cod_departamento');
}

}
