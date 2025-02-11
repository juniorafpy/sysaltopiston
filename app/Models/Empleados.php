<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empleados extends Model
{
    use HasFactory;

    protected $table = 'empleados'; //definicion de la tabla

    protected $primaryKey = 'cod_empleado'; // Clave primaria

    public $timestamps = false;

    use HasFactory;

    protected $fillable =[
        'cod_empleado',
        'fec_alta',
        'cod_presona',
        'cod_cargo',
        'nombre'
    ]; //campos para visualizar


    public function empleados (): HasMany
    {
        return $this->hasMany(PedidoCabecera::class, 'cod_empleado', 'cod_empleado');
    }

    public function personas()
    {
        return $this->belongsTo(Personas::class, 'cod_persona', 'cod_persona');
    }

}
