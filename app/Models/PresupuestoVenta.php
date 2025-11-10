<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresupuestoVenta extends Model
{
    use HasFactory;

    protected $table = 'presupuesto_ventas';

    protected $fillable = [
        'cliente_id',
        'recepcion_vehiculo_id',
        'diagnostico_id',
        'fecha_presupuesto',
        'estado',
        'total',
        'observaciones',
        'cod_condicion_compra',
        'cod_sucursal',
        'usuario_alta',
        'fec_alta',
        'usuario_mod',
        'fec_mod',
    ];

    protected $casts = [
        'fecha_presupuesto' => 'date',
        'total' => 'float',
        'fec_alta' => 'datetime',
        'fec_mod' => 'datetime',
    ];

    public function cliente()
    {
        return $this->belongsTo(Personas::class, 'cliente_id', 'cod_persona');
    }

    public function recepcionVehiculo()
    {
        return $this->belongsTo(RecepcionVehiculo::class, 'recepcion_vehiculo_id', 'id');
    }

    public function detalles()
    {
        return $this->hasMany(PresupuestoVentaDetalle::class);
    }

    public function condicionCompra()
    {
        return $this->belongsTo(CondicionCompra::class, 'cod_condicion_compra', 'cod_condicion_compra');
    }

    public function diagnostico()
    {
        return $this->belongsTo(Diagnostico::class);
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'presupuesto_venta_id', 'id');
    }
}
