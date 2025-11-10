<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estados extends Model
{
    protected $table = 'estados';
    protected $primaryKey = 'cod_estado';

    //protected $keyType = 'int';
    public $timestamps = false;    // la tabla no tiene created_at/updated_at

    protected $fillable = ['descripcion'];
}
