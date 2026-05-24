<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    protected $table = 'documentos';
    protected $primaryKey = 'tipo_documento';
    public $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;
    protected $fillable = ['tipo_documento', 'descripcion'];
}
