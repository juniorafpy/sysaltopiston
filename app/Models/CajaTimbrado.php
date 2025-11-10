<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CajaTimbrado extends Model
{
    use HasFactory;

    protected $table = 'caja_timbrado';
    protected $primaryKey = 'cod_caja_timbrado';

    protected $fillable = [
        'cod_caja',
        'cod_timbrado',
        'activo',
        'fecha_asignacion',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fecha_asignacion' => 'date',
    ];

    /**
     * Relación con Caja
     */
    public function caja()
    {
        return $this->belongsTo(Caja::class, 'cod_caja', 'cod_caja');
    }

    /**
     * Relación con Timbrado
     */
    public function timbrado()
    {
        return $this->belongsTo(Timbrado::class, 'cod_timbrado', 'cod_timbrado');
    }

    /**
     * Obtener el timbrado activo de una caja
     */
    public static function obtenerTimbradoDeCaja(int $codCaja)
    {
        return self::where('cod_caja', $codCaja)
            ->where('activo', true)
            ->with('timbrado')
            ->first()
            ?->timbrado;
    }

    /**
     * Scope para asignaciones activas
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }
}
