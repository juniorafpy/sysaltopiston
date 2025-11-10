<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sucursal extends Model
{
    use HasFactory;

    protected $table = 'sucursal'; //definicion de la tabla

    protected $primaryKey = 'cod_sucursal'; // Clave primaria

    const UPDATED_AT = null;
    public $timestamps = false;

    protected $fillable =[
        //'cod_pais',
        'descripcion',

    ]; //campos para visualizar

     public function usuario_suc (): BelongsTo
    {
        return $this->belongsTo(User::class, 'cod_sucursal', 'cod_sucursal');
    }
}
