<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecaudacionDepositar extends Model
{
    use HasFactory;

    protected $table = 'recaudaciones_depositar';
    protected $primaryKey = 'cod_recaudacion';

    public $timestamps = false;

    protected $fillable = [
        'cod_recaudacion',
        'monto',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];
}
