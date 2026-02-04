<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class Nota extends Model
{
    use HasFactory;

    protected $table = 'notas';
    protected $primaryKey = 'cod_nota';

    protected $fillable = [
        'tipo_nota',
        'tipo_operacion',
        'cod_factura',
        'cod_timbrado',
        'numero_nota',
        'fecha_emision',
        'motivo',
        'subtotal_gravado_10',
        'subtotal_gravado_5',
        'subtotal_exenta',
        'total_iva_10',
        'total_iva_5',
        'monto_total',
        'estado',
        'observaciones',
        'cod_sucursal',
        'usuario_alta',
        'fecha_alta',
        'usuario_mod',
        'fecha_mod'
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_alta' => 'datetime',
        'fecha_mod' => 'datetime',
        'subtotal_gravado_10' => 'decimal:2',
        'subtotal_gravado_5' => 'decimal:2',
        'subtotal_exenta' => 'decimal:2',
        'total_iva_10' => 'decimal:2',
        'total_iva_5' => 'decimal:2',
        'monto_total' => 'decimal:2'
    ];

    /**
     * Relaciones
     */
    public function factura()
    {
        return $this->belongsTo(Factura::class, 'cod_factura', 'cod_factura');
    }

    public function timbrado()
    {
        return $this->belongsTo(Timbrado::class, 'cod_timbrado', 'cod_timbrado');
    }

    public function detalles()
    {
        return $this->hasMany(NotaDetalle::class, 'cod_nota', 'cod_nota');
    }

    public function usuarioAlta()
    {
        return $this->belongsTo(User::class, 'usuario_alta');
    }

    public function usuarioModificacion()
    {
        return $this->belongsTo(User::class, 'usuario_mod');
    }

    /**
     * Scopes
     */
    public function scopeCredito($query)
    {
        return $query->where('tipo_nota', 'credito');
    }

    public function scopeDebito($query)
    {
        return $query->where('tipo_nota', 'debito');
    }

    public function scopeEmitidas($query)
    {
        return $query->where('estado', 'Emitida');
    }

    public function scopeAnuladas($query)
    {
        return $query->where('estado', 'Anulada');
    }

    /**
     * Accessors
     */
    public function getTipoNotaLabelAttribute(): string
    {
        return match($this->tipo_nota) {
            'credito' => 'Nota de Crédito',
            'debito' => 'Nota de Débito',
            default => $this->tipo_nota
        };
    }

    public function getEfectoAttribute(): string
    {
        return $this->tipo_nota === 'credito' ? 'Resta' : 'Suma';
    }

    /**
     * Métodos de negocio
     */

    /**
     * Crear nota completa con detalles en una transacción
     */
    public static function crearNotaCompleta(array $data): self
    {
        return DB::transaction(function () use ($data) {
            // Validar que la factura existe y está emitida
            $factura = Factura::findOrFail($data['cod_factura']);

            if ($factura->estado !== 'Emitida') {
                throw new \Exception('Solo se pueden crear notas para facturas emitidas');
            }

            // Validar monto para nota de crédito (no debe exceder saldo)
            if ($data['tipo_nota'] === 'credito') {
                $saldoActual = $factura->getSaldoConNotas();
                if ($data['monto_total'] > $saldoActual) {
                    throw new \Exception('El monto de la nota de crédito no puede exceder el saldo de la factura');
                }
            }

            // Crear la nota
            $nota = self::create([
                'tipo_nota' => $data['tipo_nota'],
                'tipo_operacion' => $data['tipo_operacion'] ?? null,
                'cod_factura' => $data['cod_factura'],
                'cod_timbrado' => $data['cod_timbrado'],
                'numero_nota' => $data['numero_nota'],
                'fecha_emision' => $data['fecha_emision'],
                'motivo' => $data['motivo'],
                'subtotal_gravado_10' => $data['subtotal_gravado_10'] ?? 0,
                'subtotal_gravado_5' => $data['subtotal_gravado_5'] ?? 0,
                'subtotal_exenta' => $data['subtotal_exenta'] ?? 0,
                'total_iva_10' => $data['total_iva_10'] ?? 0,
                'total_iva_5' => $data['total_iva_5'] ?? 0,
                'monto_total' => $data['monto_total'],
                'observaciones' => $data['observaciones'] ?? null,
                'usuario_alta' => Auth::id(),
                'fecha_alta' => now(),
            ]);

            // Crear detalles si existen
            if (isset($data['detalles']) && is_array($data['detalles'])) {
                foreach ($data['detalles'] as $detalle) {
                    // En devoluciones, solo guardar los ítems seleccionados
                    if (($data['tipo_operacion'] ?? null) === 'devolucion') {
                        if (!($detalle['seleccionado'] ?? false)) {
                            continue;
                        }
                        // Remover el campo seleccionado antes de guardar
                        unset($detalle['seleccionado']);
                    }

                    $nota->detalles()->create($detalle);
                }
            }

            return $nota->fresh(['detalles', 'factura']);
        });
    }

    /**
     * Anular esta nota
     */
    public function anular(string $motivo = null): bool
    {
        return DB::transaction(function () use ($motivo) {
            $this->estado = 'Anulada';
            $this->observaciones = ($this->observaciones ? $this->observaciones . "\n" : '')
                . "Anulada: " . ($motivo ?? 'Sin motivo especificado');
            $this->usuario_mod = Auth::id();
            $this->fecha_mod = now();

            return $this->save();
        });
    }

    /**
     * Verificar si la nota puede ser anulada
     */
    public function puedeAnularse(): bool
    {
        return $this->estado === 'Emitida';
    }

    /**
     * Calcular el impacto de esta nota en el saldo de la factura
     * Retorna valor positivo para débito, negativo para crédito
     */
    public function getImpactoSaldo(): float
    {
        if ($this->estado === 'Anulada') {
            return 0;
        }

        return $this->tipo_nota === 'credito'
            ? -$this->monto_total
            : $this->monto_total;
    }
}
