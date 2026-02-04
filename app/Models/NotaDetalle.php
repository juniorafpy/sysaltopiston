<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotaDetalle extends Model
{
    use HasFactory;

    protected $table = 'notas_detalle';
    protected $primaryKey = 'cod_nota_detalle';

    protected $fillable = [
        'cod_nota',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'tipo_iva',
        'subtotal',
        'monto_iva',
        'total',
        'cod_factura_detalle'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'monto_iva' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    /**
     * Relaciones
     */
    public function nota()
    {
        return $this->belongsTo(Nota::class, 'cod_nota', 'cod_nota');
    }

    // Relación comentada porque facturas_detalle puede no existir
    // public function facturaDetalle()
    // {
    //     return $this->belongsTo(FacturaDetalle::class, 'cod_factura_detalle', 'cod_factura_detalle');
    // }

    /**
     * Boot - Calcular totales automáticamente
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($detalle) {
            $detalle->calcularTotales();
        });

        static::updating(function ($detalle) {
            $detalle->calcularTotales();
        });
    }

    /**
     * Calcular subtotal, IVA y total basado en cantidad, precio y tipo de IVA
     */
    public function calcularTotales(): void
    {
        $this->subtotal = $this->cantidad * $this->precio_unitario;

        // Calcular IVA según el tipo
        $this->monto_iva = match($this->tipo_iva) {
            '10%' => $this->subtotal * 0.10,
            '5%' => $this->subtotal * 0.05,
            'Exenta' => 0,
            default => 0
        };

        $this->total = $this->subtotal + $this->monto_iva;
    }

    /**
     * Accessor para obtener la tasa de IVA como número
     */
    public function getTasaIvaAttribute(): float
    {
        return match($this->tipo_iva) {
            '10%' => 10,
            '5%' => 5,
            'Exenta' => 0,
            default => 0
        };
    }
}
