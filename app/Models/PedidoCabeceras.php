<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PedidoCabeceras extends Model
{
    protected $table = 'pedidos_cabeceras'; //definicion de la tabla

    protected $primaryKey = 'cod_pedido'; // Clave primaria

    public $timestamps = false;

    use HasFactory;

    protected $fillable = [
        'fec_pedido',
        'cod_empleado',
        'cod_sucursal',
        'usuario_alta',
        'fec_alta',
        'estado'
    ];

    protected static function booted(): void
    {
        // Esto se ejecuta JUSTO ANTES de que se cree un nuevo registro
        static::creating(function (PedidoCabeceras $pedido) {
            // Asignamos el valor 'pendiente' al campo 'estado'
            $pedido->estado = 'PENDIENTE';
            $pedido->fec_alta = Carbon::now('America/Asuncion');
        });
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(PedidoDetalle::class, 'cod_pedido', 'cod_pedido');
    }

    public function ped_empleados(): belongsTo
    {
        return $this->belongsTo(Empleados::class, 'cod_empleado', 'cod_empleado');
    }

    public function sucursal_ped(): belongsTo
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'cod_proveedor');
    }
}
