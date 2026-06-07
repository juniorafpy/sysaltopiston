<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Procesadora extends Model
{
    protected $table = 'procesadora';
    protected $primaryKey = 'cod_procesadora';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'cod_procesadora',
        'descripcion',
    ];
}
