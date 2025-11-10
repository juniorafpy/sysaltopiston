<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class AperturaCaja extends Model
{
    use HasFactory;

    protected $table = 'aperturas_caja';
    protected $primaryKey = 'cod_apertura';

    protected $fillable = [
        'cod_caja',
        'cod_cajero',
        'cod_sucursal',
        'fecha_apertura',
        'hora_apertura',
        'monto_inicial',
        'observaciones_apertura',
        'fecha_cierre',
        'hora_cierre',
        'efectivo_real',
        'saldo_esperado',
        'diferencia',
        'monto_depositar',
        'observaciones_cierre',
        'estado',
        'usuario_alta',
        'fecha_alta',
        'usuario_mod',
        'fecha_mod',
    ];

    protected $casts = [
        'fecha_apertura' => 'date',
        'hora_apertura' => 'datetime',
        'fecha_cierre' => 'date',
        'hora_cierre' => 'datetime',
        'monto_inicial' => 'decimal:2',
        'efectivo_real' => 'decimal:2',
        'saldo_esperado' => 'decimal:2',
        'diferencia' => 'decimal:2',
        'monto_depositar' => 'decimal:2',
        'fecha_alta' => 'datetime',
        'fecha_mod' => 'datetime',
    ];

    /**
     * Relación con Caja
     */
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class, 'cod_caja', 'cod_caja');
    }

    /**
     * Relación con Cajero (Empleado)
     */
    public function cajero(): BelongsTo
    {
        return $this->belongsTo(Empleados::class, 'cod_cajero', 'cod_empleado');
    }

    /**
     * Relación con Sucursal
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    /**
     * Relación con Movimientos de Caja
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoCaja::class, 'cod_apertura', 'cod_apertura');
    }

    /**
     * Relación con Usuario Alta
     */
    public function usuarioAlta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_alta');
    }

    /**
     * Relación con Usuario Modificación
     */
    public function usuarioMod(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_mod');
    }

    /**
     * Scope para aperturas abiertas
     */
    public function scopeAbiertas($query)
    {
        return $query->where('estado', 'Abierta');
    }

    /**
     * Scope para aperturas cerradas
     */
    public function scopeCerradas($query)
    {
        return $query->where('estado', 'Cerrada');
    }

    /**
     * Scope para aperturas del cajero actual
     */
    public function scopeDelCajeroActual($query)
    {
        return $query->where('cod_cajero', Auth::id());
    }

    /**
     * Scope para aperturas de una caja específica
     */
    public function scopeDeCaja($query, $codCaja)
    {
        return $query->where('cod_caja', $codCaja);
    }

    /**
     * Accessor: Calcular total de ingresos
     */
    public function getTotalIngresosAttribute()
    {
        return $this->movimientos()
            ->where('tipo_movimiento', 'Ingreso')
            ->sum('monto');
    }

    /**
     * Accessor: Calcular total de egresos
     */
    public function getTotalEgresosAttribute()
    {
        return $this->movimientos()
            ->where('tipo_movimiento', 'Egreso')
            ->sum('monto');
    }

    /**
     * Accessor: Calcular saldo esperado (monto_inicial + ingresos - egresos)
     */
    public function getSaldoEsperadoCalculadoAttribute()
    {
        return $this->monto_inicial + $this->total_ingresos - $this->total_egresos;
    }

    /**
     * Accessor: Verificar si hay diferencia
     */
    public function getTieneDiferenciaAttribute()
    {
        return $this->diferencia != 0;
    }

    /**
     * Accessor: Tipo de diferencia (Sobrante, Faltante, OK)
     */
    public function getTipoDiferenciaAttribute()
    {
        if (!$this->diferencia) {
            return 'OK';
        }
        return $this->diferencia > 0 ? 'Sobrante' : 'Faltante';
    }

    /**
     * Verificar si el cajero ya tiene una caja abierta
     */
    public static function cajeroTieneCajaAbierta($codCajero = null): bool
    {
        $cajero = $codCajero ?? Auth::id();

        return self::where('cod_cajero', $cajero)
            ->where('estado', 'Abierta')
            ->exists();
    }

    /**
     * Verificar si la caja específica ya está abierta
     */
    public static function cajaEstaAbierta($codCaja): bool
    {
        return self::where('cod_caja', $codCaja)
            ->where('estado', 'Abierta')
            ->exists();
    }

    /**
     * Registrar movimiento de caja
     */
    public function registrarMovimiento(array $data)
    {
        return $this->movimientos()->create([
            'tipo_movimiento' => $data['tipo_movimiento'],
            'concepto' => $data['concepto'],
            'tipo_documento' => $data['tipo_documento'] ?? null,
            'documento_id' => $data['documento_id'] ?? null,
            'monto' => $data['monto'],
            'descripcion' => $data['descripcion'] ?? null,
            'fecha_movimiento' => $data['fecha_movimiento'] ?? now(),
            'usuario_alta' => Auth::id(),
        ]);
    }

    /**
     * Cerrar caja
     */
    public function cerrar(float $efectivoReal, ?string $observaciones = null)
    {
        $saldoEsperado = $this->saldo_esperado_calculado;
        $diferencia = $efectivoReal - $saldoEsperado;

        $this->update([
            'fecha_cierre' => now()->toDateString(),
            'hora_cierre' => now()->toTimeString(),
            'efectivo_real' => $efectivoReal,
            'saldo_esperado' => $saldoEsperado,
            'diferencia' => $diferencia,
            'monto_depositar' => $efectivoReal - $this->monto_inicial, // Lo que excede el monto inicial
            'observaciones_cierre' => $observaciones,
            'estado' => 'Cerrada',
            'usuario_mod' => Auth::id(),
            'fecha_mod' => now(),
        ]);

        return $this;
    }
}
