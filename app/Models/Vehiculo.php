<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    use HasFactory;

    protected $table = 'vehiculos';
    public $timestamps = false;

    protected $fillable = [
        'marca_id',
        'modelo_id',
        'matricula',
        'anio',
        'color_id',
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

    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id', 'cod_color');
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id', 'cod_cliente');
    }

    public function recepciones()
    {
        return $this->hasMany(RecepcionVehiculo::class, 'vehiculo_id', 'id');
    }
}
