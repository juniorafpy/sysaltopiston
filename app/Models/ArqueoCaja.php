<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArqueoCaja extends Model
{
    use HasFactory;

    protected $table = 'arqueos_caja';
    protected $primaryKey = 'cod_arqueo';
    public $timestamps = false;

    protected $fillable = [
        'cod_apertura',
        'efectivo_sistema',
        'tarjetas_sistema',
        'transferencias_sistema',
        'cheques_sistema',
        'total_sistema',
        'efectivo_fisico',
        'tarjetas_fisico',
        'transferencias_fisico',
        'cheques_fisico',
        'total_fisico',
        'diferencia',
        'observaciones',
        'usuario_alta',
        'fecha_alta',
    ];

    protected $casts = [
        'efectivo_sistema' => 'decimal:2',
        'tarjetas_sistema' => 'decimal:2',
        'transferencias_sistema' => 'decimal:2',
        'cheques_sistema' => 'decimal:2',
        'total_sistema' => 'decimal:2',
        'efectivo_fisico' => 'decimal:2',
        'tarjetas_fisico' => 'decimal:2',
        'transferencias_fisico' => 'decimal:2',
        'cheques_fisico' => 'decimal:2',
        'total_fisico' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'fecha_alta' => 'datetime',
    ];

    public function apertura()
    {
        return $this->belongsTo(AperturaCaja::class, 'cod_apertura', 'cod_apertura');
    }
}
