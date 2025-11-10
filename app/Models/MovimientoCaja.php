<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoCaja extends Model
{
    use HasFactory;

    protected $table = 'movimientos_caja';
    protected $primaryKey = 'cod_movimiento';

    protected $fillable = [
        'cod_apertura',
        'tipo_movimiento',
        'concepto',
        'tipo_documento',
        'documento_id',
        'monto',
        'descripcion',
        'fecha_movimiento',
        'usuario_alta',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha_movimiento' => 'datetime',
        'fecha_alta' => 'datetime',
    ];

    /**
     * Relación con Apertura de Caja
     */
    public function aperturaCaja(): BelongsTo
    {
        return $this->belongsTo(AperturaCaja::class, 'cod_apertura', 'cod_apertura');
    }

    /**
     * Relación con Usuario Alta
     */
    public function usuarioAlta(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_alta');
    }

    /**
     * Scope para ingresos
     */
    public function scopeIngresos($query)
    {
        return $query->where('tipo_movimiento', 'Ingreso');
    }

    /**
     * Scope para egresos
     */
    public function scopeEgresos($query)
    {
        return $query->where('tipo_movimiento', 'Egreso');
    }

    /**
     * Scope por concepto
     */
    public function scopePorConcepto($query, $concepto)
    {
        return $query->where('concepto', $concepto);
    }

    /**
     * Scope por tipo de documento
     */
    public function scopePorTipoDocumento($query, $tipoDocumento)
    {
        return $query->where('tipo_documento', $tipoDocumento);
    }

    /**
     * Accessor: Tipo de movimiento en español
     */
    public function getTipoMovimientoTextoAttribute()
    {
        return $this->tipo_movimiento === 'Ingreso' ? 'Ingreso' : 'Egreso';
    }

    /**
     * Accessor: Monto formateado
     */
    public function getMontoFormateadoAttribute()
    {
        return number_format($this->monto, 0, ',', '.') . ' Gs.';
    }
}
