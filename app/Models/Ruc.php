<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ruc extends Model
{
    use HasFactory;

    protected $table = 'rucs'; //definicion de la tabla

    protected $primaryKey = 'ruc'; // Clave primaria

    public $timestamps = false;

    protected $fillable =[
        'ruc','nombre','div'
        ]; //campos para visualizar


}
