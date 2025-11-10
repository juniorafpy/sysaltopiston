<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CobroFormaPago extends Model
{
    use HasFactory;

    protected $table = 'cobros_formas_pago';
    protected $primaryKey = 'cod_forma_pago';

    protected $fillable = [
        'cod_cobro',
        'tipo_transaccion',
        'monto',
        'cod_entidad_bancaria',
        'numero_voucher',
        'numero_cheque'
    ];

    protected $casts = [
        'monto' => 'decimal:2'
    ];

    /**
     * Relaciones
     */
    public function cobro()
    {
        return $this->belongsTo(Cobro::class, 'cod_cobro', 'cod_cobro');
    }

    public function entidadBancaria()
    {
        return $this->belongsTo(EntidadBancaria::class, 'cod_entidad_bancaria', 'cod_entidad_bancaria');
    }

    /**
     * Obtiene el label del tipo de transacción
     */
    public function getTipoTransaccionLabel(): string
    {
        return match ($this->tipo_transaccion) {
            'efectivo' => 'Efectivo',
            'tarjeta_credito' => 'Tarjeta de Crédito',
            'tarjeta_debito' => 'Tarjeta de Débito',
            'cheque' => 'Cheque',
            'transferencia' => 'Transferencia Bancaria',
            default => $this->tipo_transaccion
        };
    }
}
