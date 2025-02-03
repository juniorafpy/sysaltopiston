<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Articulos extends Model
{
    use HasFactory;

    protected $table = 'articulos'; //definicion de la tabla

    protected $primaryKey = 'cod_articulo'; // Clave primaria

    public $timestamps = false;

    protected $fillable = [
        //'cod_pais',
        'descripcion',
        'cod_marca',
        'cod_modelo',
        'precio',
        'cod_medida',
        'cod_tip_articulo',
        'activo',
        'costo',
        'usuario_alta',
        'fec_alta'

    ]; //campos para visualizar


            public function marcas_ar(): HasMany
        {
            return $this->hasMany(Marcas::class, 'cod_marca', 'cod_marca');

        }

        public function modelos_ar(): HasMany
        {
            return $this->hasMany(Modelos::class, 'cod_modelo', 'cod_modelo');

        }

            public function medida_ar (): HasMany
        {
            return $this->hasMany(Medidas::class, 'cod_medida', 'cod_medida');

        }

        public function tipo_articulo_ar (): HasMany
        {
            return $this->hasMany(TipoArticulos::class, 'cod_tip_articulo', 'cod_tip_articulo');

        }

}


