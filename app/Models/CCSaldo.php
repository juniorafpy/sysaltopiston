<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CCSaldo extends Model
{
    use HasFactory;

    protected $table = 'cc_saldos';
    protected $primaryKey = 'cod_saldo';

    protected $fillable = [
        'cod_cliente',
        'tipo_comprobante',
        'nro_comprobante',
        'fecha_comprobante',
        'debe',
        'haber',
        'saldo_actual',
        'descripcion',
        'cod_factura',
        'usuario_alta',
        'fecha_alta'
    ];

    protected $casts = [
        'fecha_comprobante' => 'date',
        'debe' => 'decimal:2',
        'haber' => 'decimal:2',
        'saldo_actual' => 'decimal:2',
        'fecha_alta' => 'datetime'
    ];

    /**
     * Relaciones
     */
    public function cliente()
    {
        return $this->belongsTo(Personas::class, 'cod_cliente', 'cod_persona');
    }

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'cod_factura', 'cod_factura');
    }

    public function usuarioAlta()
    {
        return $this->belongsTo(User::class, 'usuario_alta', 'id');
    }

    /**
     * Obtiene el saldo actual de un cliente
     */
    public static function obtenerSaldoCliente(int $codCliente): float
    {
        $ultimoSaldo = self::where('cod_cliente', $codCliente)
            ->orderBy('fecha_comprobante', 'desc')
            ->orderBy('cod_saldo', 'desc')
            ->first();

        return $ultimoSaldo ? $ultimoSaldo->saldo_actual : 0;
    }

    /**
     * Registra un pago (haber)
     */
    public static function registrarPago(int $codCliente, float $monto, string $nroComprobante, string $descripcion = null): self
    {
        $saldoAnterior = self::obtenerSaldoCliente($codCliente);
        $nuevoSaldo = $saldoAnterior - $monto;

        return self::create([
            'cod_cliente' => $codCliente,
            'tipo_comprobante' => 'Recibo',
            'nro_comprobante' => $nroComprobante,
            'fecha_comprobante' => now()->toDateString(),
            'debe' => 0,
            'haber' => $monto,
            'saldo_actual' => $nuevoSaldo,
            'descripcion' => $descripcion ?? "Pago recibido - Recibo Nro: {$nroComprobante}",
            'usuario_alta' => auth()->id()
        ]);
    }

    /**
     * Scope para movimientos de un cliente
     */
    public function scopeDeCliente($query, int $codCliente)
    {
        return $query->where('cod_cliente', $codCliente);
    }

    /**
     * Scope para movimientos con saldo pendiente
     */
    public function scopeConSaldoPendiente($query)
    {
        return $query->where('saldo_actual', '>', 0);
    }

    /**
     * Scope para movimientos entre fechas
     */
    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_comprobante', [$fechaInicio, $fechaFin]);
    }

    /**
     * Scope para débitos (facturas)
     */
    public function scopeDebitos($query)
    {
        return $query->where('debe', '>', 0);
    }

    /**
     * Scope para créditos (pagos)
     */
    public function scopeCreditos($query)
    {
        return $query->where('haber', '>', 0);
    }

    /**
     * Accessor para el tipo de movimiento
     */
    public function getTipoMovimientoAttribute(): string
    {
        if ($this->debe > 0) {
            return 'Débito';
        } elseif ($this->haber > 0) {
            return 'Crédito';
        }
        return 'Sin movimiento';
    }

    /**
     * Accessor para el monto (debe o haber)
     */
    public function getMontoAttribute(): float
    {
        return $this->debe > 0 ? $this->debe : $this->haber;
    }
}
