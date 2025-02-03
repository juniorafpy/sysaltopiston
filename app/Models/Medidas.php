<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Medidas extends Model
{
    use HasFactory;

    protected $table = 'medidas'; //definicion de la tabla

    protected $primaryKey = 'cod_medida'; // Clave primaria

    public $timestamps = false;

    use HasFactory;

    protected $fillable =[
        'cod_medida',
        'descripcion',

    ]; //campos para visualizar


    public function articulos (): BelongsTo
    {
        return $this->belongsTo(Articulos::class, 'cod_medida', 'cod_medida');
    }
}
