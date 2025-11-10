<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuiaRemisionCabecera extends Model
{
    use HasFactory;

    protected $table = 'guia_remision_cabecera';

    protected $fillable = [
        'compra_cabecera_id',
        'almacen_id',
        'numero_remision',
        'fecha_remision',
    ];

    public function compraCabecera(): BelongsTo
    {
        return $this->belongsTo(CompraCabecera::class, 'compra_cabecera_id', 'id_compra_cabecera');
    }

    public function almacen(): BelongsTo
    {
        return $this->belongsTo(Almacen::class, 'almacen_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(GuiaRemisionDetalle::class, 'guia_remision_cabecera_id');
    }
}
