<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class LibroIvaCompra extends Model
{
    use HasFactory;

    protected $table = 'libro_iva_compra';
    protected $primaryKey = 'cod_libro';
    public $timestamps = false;

    protected $fillable = [
        'cod_sucursal',
        'tip_comprobante',
        'nro_comprobante',
        'ser_comprobante',
        'cod_proveedor',
        'fec_comprobante',
        'iva10',
        'iva5',
        'exenta',
        'total',
        'fec_alta',
        'usuario_alta',
    ];

    protected $casts = [
        'fec_comprobante' => 'date',
        'fec_alta' => 'datetime',
        'iva10' => 'decimal:2',
        'iva5' => 'decimal:2',
        'exenta' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($libro) {
            if (!$libro->usuario_alta) {
                $libro->usuario_alta = auth()->user()->name ?? 'Sistema';
            }
            if (!$libro->fec_alta) {
                $libro->fec_alta = now();
            }
        });
    }

    /**
     * Relaciones
     */
    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'cod_proveedor', 'cod_proveedor');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }
}
