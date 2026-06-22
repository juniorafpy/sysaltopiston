<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AperturaCaja extends Model
{
    use HasFactory;

    protected $table = 'aperturas_caja';
    protected $primaryKey = 'cod_apertura';

     public $timestamps = false;

    protected $fillable = [
        'cod_caja',
        'usuario',
        'cod_sucursal',
        'fecha_apertura',
        'hora_apertura',
        'monto_inicial',
        'observaciones_apertura',
        'fecha_cierre',
        'hora_cierre',
        'saldo_esperado',
        'diferencia',
        'estado',
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'fecha_cierre' => 'date',
    ];

    public function caja()
    {
        return $this->belongsTo(Caja::class, 'cod_caja', 'cod_caja');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoCaja::class, 'cod_apertura', 'cod_apertura');
    }

    public function cobros()
    {
        return $this->hasMany(Cobro::class, 'cod_apertura', 'cod_apertura');
    }

    public function arqueos()
    {
        return $this->hasMany(ArqueoCaja::class, 'cod_apertura', 'cod_apertura');
    }

    public function scopeAbiertas($query)
    {
        return $query->where('estado', 'Abierta');
    }

    public function scopeCerradas($query)
    {
        return $query->where('estado', 'Cerrada');
    }

    public function getTotalIngresosAttribute()
    {
        return $this->movimientos()
            ->where('tipo_movimiento', 'Ingreso')
            ->sum('monto');
    }

    public function getTotalEgresosAttribute()
    {
        return $this->movimientos()
            ->where('tipo_movimiento', 'Egreso')
            ->sum('monto');
    }

    public function getSaldoEsperadoCalculadoAttribute()
    {
        return $this->monto_inicial + $this->total_ingresos - $this->total_egresos;
    }

    public function calcularTotalesCierre(): array
    {
        return [
            'saldo_esperado' => $this->saldo_esperado_calculado,
            'diferencia' => 0, // Asumimos 0 si no hay campo para efectivo real
        ];
    }

    public function cerrarCaja()
    {
        $totales = $this->calcularTotalesCierre();
        
        $this->update([
            'fecha_cierre' => now()->toDateString(),
            'hora_cierre' => now()->toTimeString(),
            'saldo_esperado' => $totales['saldo_esperado'],
            'diferencia' => $totales['diferencia'],
            'estado' => 'Cerrada',
            'usuario_mod' => Auth::id(),
            'fecha_mod' => now(),
        ]);

        return $this;
    }
}
