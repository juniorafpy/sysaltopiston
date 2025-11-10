<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    use HasFactory;

    protected $table = 'vehiculos';

    protected $fillable = [
        'marca_id',
        'modelo_id',
        'matricula',
        'anio',
        'color',
        'cliente_id',
    ];

    public function marca()
    {
        return $this->belongsTo(Marcas::class, 'marca_id', 'cod_marca');
    }

    public function modelo()
    {
        return $this->belongsTo(Modelos::class, 'modelo_id', 'cod_modelo');
    }

    public function cliente()
    {
        return $this->belongsTo(Personas::class, 'cliente_id', 'cod_persona');
    }

    public function recepciones()
    {
        return $this->hasMany(RecepcionVehiculo::class, 'vehiculo_id', 'id');
    }
}
