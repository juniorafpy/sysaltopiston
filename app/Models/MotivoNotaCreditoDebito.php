<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MotivoNotaCreditoDebito extends Model
{
    use HasFactory;

    protected $table = 'motivos_nota_credito_debito';
    protected $primaryKey = 'cod_motivo';

    protected $fillable = [
        'tipo_nota',
        'descripcion',
        'afecta_stock',
        'afecta_saldo',
        'activo',
    ];

    protected $casts = [
        'afecta_stock' => 'boolean',
        'afecta_saldo' => 'boolean',
        'activo' => 'boolean',
    ];

    /**
     * Scope para motivos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para motivos de notas de crédito
     */
    public function scopeNotasCredito($query)
    {
        return $query->where('tipo_nota', 'NC');
    }

    /**
     * Scope para motivos de notas de débito
     */
    public function scopeNotasDebito($query)
    {
        return $query->where('tipo_nota', 'ND');
    }

    /**
     * Relación con notas de crédito/débito
     */
    public function notas()
    {
        return $this->hasMany(NotaCreditoDebitoCompra::class, 'cod_motivo', 'cod_motivo');
    }
}
