<?php

// app/Observers/PaisObserver.php
namespace App\Observers;

use App\Models\Pais;

class PaisObserver
{
    public function creating(Pais $pais)
    {
        // Convierte los campos a mayúsculas antes de guardar
      //  $pais->cod_pais = strtoupper($pais->cod_pais);
        $pais->descripcion = strtoupper($pais->descripcion);
        $pais->gentilicio = strtoupper($pais->gentilicio);
        $pais->abreviatura = strtoupper($pais->abreviatura);
    }

    public function updating(Pais $pais)
    {
        // Convierte los campos a mayúsculas antes de actualizar
       // $pais->cod_pais = strtoupper($pais->cod_pais);
        $pais->descripcion = strtoupper($pais->descripcion);
        $pais->gentilicio = strtoupper($pais->gentilicio);
        $pais->abreviatura = strtoupper($pais->abreviatura);
    }
}
