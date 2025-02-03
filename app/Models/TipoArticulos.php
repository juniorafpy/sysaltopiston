<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TipoArticulos extends Model
{
    use HasFactory;

    protected $table = 'tipos_articulos'; //definicion de la tabla

    protected $primaryKey = 'cod_tip_articulo'; // Clave primaria

    public $timestamps = false;

    use HasFactory;

    protected $fillable =[
        'cod_tip_articulo',
        'descripcion',

    ]; //campos para visualizar


    public function tip_articulos (): BelongsTo
    {
        return $this->belongsTo(Articulos::class, 'cod_tip_articulo', 'cod_tip_articulo');
    }
}

