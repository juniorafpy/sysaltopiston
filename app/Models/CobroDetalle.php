<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CobroDetalle extends Model
{
    use HasFactory;

    protected $table = 'cobros_detalle';
    protected $primaryKey = 'cod_cobro_detalle';

    protected $fillable = [
        'cod_cobro',
        'cod_factura',
        'numero_cuota',
        'monto_cuota'
    ];

    protected $casts = [
        'monto_cuota' => 'decimal:2'
    ];

    /**
     * Relaciones
     */
    public function cobro()
    {
        return $this->belongsTo(Cobro::class, 'cod_cobro', 'cod_cobro');
    }

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'cod_factura', 'cod_factura');
    }
}
