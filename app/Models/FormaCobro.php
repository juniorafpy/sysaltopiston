<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormaCobro extends Model
{
    protected $table = 'forma_cobro';
    protected $primaryKey = 'cod_forma_cobro';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'cod_forma_cobro',
        'descripcion',
    ];
}
