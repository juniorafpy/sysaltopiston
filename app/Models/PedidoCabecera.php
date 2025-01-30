<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PedidoCabecera extends Model
{
    protected $table = 'pedidos_cabecera'; //definicion de la tabla

    protected $primaryKey = 'cod_pedido'; // Clave primaria

    public $timestamps = false;

    use HasFactory;

    protected $fillable = [
        'fec_pedido',
        'cod_empleado',
        'usuario_alta',
        'fec_alta'
    ]; //campos para visualizar


    public function detalles(): HasMany
    {
        return $this->hasMany(PedidoDetalle::class, 'cod_pedido', 'cod_pedido');
    }

    public function ped_empleados(): belongsTo
    {
        return $this->belongsTo(Empleados::class, 'cod_empleado', 'cod_empleado');
    }
}
