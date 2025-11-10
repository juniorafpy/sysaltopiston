<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Modelos extends Model
{
    use HasFactory;

    protected $table = 'st_modelos'; //definicion de la tabla

    protected $primaryKey = 'cod_modelo'; // Clave primaria

    const UPDATED_AT = null;
    public $timestamps = false;

    protected $fillable =[
        //'cod_pais',
        'descripcion',
        'cod_marca',
        'usuario_alta',
        'fec_alta'

    ]; //campos para visualizar

    // En el modelo Pais.php
public function marca(): BelongsTo
{
    return $this->belongsTo(Marcas::class, 'cod_marca', 'cod_marca');
}

}
