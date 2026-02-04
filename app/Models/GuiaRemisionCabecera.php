<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuiaRemisionCabecera extends Model
{
    use HasFactory;

    protected $table = 'remision_cabecera';

    protected $fillable = [
        'compra_cabecera_id',
        'almacen_id',
        'tipo_comprobante',
        'ser_remision',
        'numero_remision',
        'fecha_remision',
        'cod_empleado',
        'cod_sucursal',
        'usuario_alta',
        'fec_alta',
        'usuario_mod',
        'fec_mod',
        'estado',
        'observacion',
    ];

    public function compraCabecera(): BelongsTo
    {
        return $this->belongsTo(CompraCabecera::class, 'compra_cabecera_id', 'id_compra_cabecera');
    }

    // almacen_id es realmente el cod_sucursal (depósito destino)
    // public function almacen(): BelongsTo
    // {
    //     return $this->belongsTo(Almacen::class, 'almacen_id');
    // }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(GuiaRemisionDetalle::class, 'guia_remision_cabecera_id');
    }
}
