<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AjusteCabecera extends Model
{
    use HasFactory;

    protected $table = 'ajuste_cabecera';
    protected $primaryKey = 'nro_ajuste';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'cod_sucursal',
        'tipo',
        'serie',
        'nro_ajuste',
        'fec_ajuste',
        'tipo_ajuste',
        'estado',
        'fec_alta',
        'usuario_alta',
    ];

    protected $casts = [
        'fec_ajuste' => 'date',
        'fec_alta' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ajuste) {
            if (!$ajuste->usuario_alta) {
                $ajuste->usuario_alta = auth()->user()->name ?? 'Sistema';
            }
            if (!$ajuste->fec_alta) {
                $ajuste->fec_alta = now();
            }
            if (!$ajuste->estado) {
                $ajuste->estado = 'P';
            }
            if (!$ajuste->cod_sucursal) {
                $ajuste->cod_sucursal = 1;
            }
            
            // Valores fijos para cabecera
            $ajuste->tipo = 'AJS';
            $ajuste->serie = 'A';
        });
    }

    public function tipoAjuste()
    {
        return $this->belongsTo(TipoAjuste::class, 'tipo_ajuste', 'cod_tipo');
    }

    public function sucursal()
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    public function detalles()
    {
        return $this->hasMany(AjusteDetalle::class, 'nro_ajuste', 'nro_ajuste')
                    ->where('serie', $this->serie)
                    ->where('tipo', $this->tipo);
    }

    public function getEsEditableAttribute()
    {
        return $this->estado === 'P';
    }
}
