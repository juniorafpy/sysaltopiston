<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacturaDetalle extends Model
{
    use HasFactory;

    protected $table = 'factura_detalles';
    protected $primaryKey = 'cod_detalle';

    protected $fillable = [
        'cod_factura',
        'cod_articulo',
        'descripcion',
        'cantidad',
        'precio_unitario',
        'porcentaje_descuento',
        'monto_descuento',
        'subtotal',
        'tipo_iva',
        'porcentaje_iva',
        'monto_iva',
        'total'
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'precio_unitario' => 'decimal:2',
        'porcentaje_descuento' => 'decimal:2',
        'monto_descuento' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'porcentaje_iva' => 'decimal:2',
        'monto_iva' => 'decimal:2',
        'total' => 'decimal:2'
    ];

    /**
     * Relaciones
     */
    public function factura()
    {
        return $this->belongsTo(Factura::class, 'cod_factura', 'cod_factura');
    }

    public function articulo()
    {
        return $this->belongsTo(Articulos::class, 'cod_articulo', 'cod_articulo');
    }

    /**
     * Calcula el subtotal (cantidad * precio_unitario - descuento)
     */
    public static function calcularSubtotal(float $cantidad, float $precioUnitario, float $porcentajeDescuento = 0): float
    {
        $importe = $cantidad * $precioUnitario;
        $montoDescuento = ($importe * $porcentajeDescuento) / 100;
        return $importe - $montoDescuento;
    }

    /**
     * Calcula el monto de descuento
     */
    public static function calcularMontoDescuento(float $cantidad, float $precioUnitario, float $porcentajeDescuento): float
    {
        $importe = $cantidad * $precioUnitario;
        return ($importe * $porcentajeDescuento) / 100;
    }

    /**
     * Calcula el monto de IVA según el tipo
     */
    public static function calcularMontoIva(float $subtotal, string $tipoIva): float
    {
        switch ($tipoIva) {
            case '10':
                return ($subtotal * 10) / 110; // IVA incluido
            case '5':
                return ($subtotal * 5) / 105; // IVA incluido
            case 'Exenta':
            default:
                return 0;
        }
    }

    /**
     * Obtiene el porcentaje de IVA según el tipo
     */
    public static function obtenerPorcentajeIva(string $tipoIva): float
    {
        switch ($tipoIva) {
            case '10':
                return 10;
            case '5':
                return 5;
            case 'Exenta':
            default:
                return 0;
        }
    }

    /**
     * Calcula el total (subtotal + IVA)
     * Nota: Si el precio ya incluye IVA, el total es igual al subtotal
     */
    public static function calcularTotal(float $subtotal, float $montoIva): float
    {
        return $subtotal; // Asumiendo que el precio incluye IVA
    }

    /**
     * Calcula todos los valores de un detalle
     */
    public static function calcularDetalle(array $data): array
    {
        $cantidad = $data['cantidad'];
        $precioUnitario = $data['precio_unitario'];
        $porcentajeDescuento = $data['porcentaje_descuento'] ?? 0;
        $tipoIva = $data['tipo_iva'] ?? '10';

        $montoDescuento = self::calcularMontoDescuento($cantidad, $precioUnitario, $porcentajeDescuento);
        $subtotal = self::calcularSubtotal($cantidad, $precioUnitario, $porcentajeDescuento);
        $porcentajeIva = self::obtenerPorcentajeIva($tipoIva);
        $montoIva = self::calcularMontoIva($subtotal, $tipoIva);
        $total = $subtotal; // El precio ya incluye IVA

        return [
            'cantidad' => $cantidad,
            'precio_unitario' => $precioUnitario,
            'porcentaje_descuento' => $porcentajeDescuento,
            'monto_descuento' => $montoDescuento,
            'subtotal' => $subtotal,
            'tipo_iva' => $tipoIva,
            'porcentaje_iva' => $porcentajeIva,
            'monto_iva' => $montoIva,
            'total' => $total
        ];
    }

    /**
     * Accessor para el importe sin descuento
     */
    public function getImporteBrutoAttribute(): float
    {
        return $this->cantidad * $this->precio_unitario;
    }

    /**
     * Accessor para el precio unitario sin IVA (gravado)
     */
    public function getPrecioUnitarioGravadoAttribute(): float
    {
        switch ($this->tipo_iva) {
            case '10':
                return ($this->precio_unitario * 100) / 110;
            case '5':
                return ($this->precio_unitario * 100) / 105;
            case 'Exenta':
            default:
                return $this->precio_unitario;
        }
    }

    /**
     * Accessor para el subtotal gravado (sin IVA)
     */
    public function getSubtotalGravadoAttribute(): float
    {
        return $this->subtotal - $this->monto_iva;
    }
}
