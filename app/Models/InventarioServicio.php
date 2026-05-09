<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventarioServicio extends Model
{
    use HasFactory;

     protected $table = 'sm_inventario';

    protected $primaryKey = 'cod_inventario';

    public $timestamps = false;

    protected $fillable = [
        'descripcion',
        'estado',
        'tipo',
    ];

    public function recepcionVehiculos()
    {
        return $this->belongsToMany(
            RecepcionVehiculo::class,
            'recepcion_vehiculo_items_inventario',
            'cod_inventario',
            'recepcion_vehiculo_id',
            'cod_inventario',
            'id'
        );
    }

}
