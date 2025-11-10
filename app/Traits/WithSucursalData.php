<?php

namespace App\Traits;

use App\Models\Sucursal;
use App\Models\Empleados;
use Illuminate\Support\Facades\Auth;

trait WithSucursalData
{
    //usuario alta
    public $usuario_alta;

    // Las propiedades que necesitarán tus páginas
    public $cod_sucursal;
    public $nombre_sucursal;

    public $cod_empleado;
    public $nombre_empleado;


    /**
     * Este método inicializa los datos de la sucursal.
     * Lo llamaremos desde el mount() de nuestras páginas.
     */
    public function initSucursalData(): void
    {
        // 1. Obtenemos el código de la sucursal del usuario
        $this->cod_sucursal = Auth::user()->cod_sucursal;

        // 2. Buscamos el nombre de la sucursal UNA SOLA VEZ
        $sucursal = Sucursal::find($this->cod_sucursal);

        // 3. Asignamos el nombre para usarlo en el formulario
        $this->nombre_sucursal = $sucursal ? $sucursal->descripcion : 'Sucursal no encontrada';
    }

    public function initUsuAltaData(): void
    {
        // 1. Obtenemos el código de la sucursal del usuario
        $this->usuario_alta = Auth::user()->name;

    }

    public function initEmpleadoData(): void
    {
        // 1. Obtenemos el código de la sucursal del usuario
        $this->cod_empleado = Empleados::where('cod_persona', Auth::user()->cod_persona)->value('cod_empleado');
        $empleado = Empleados::find($this->cod_empleado);
        // 3. Asignamos el nombre para usarlo en el
        $this->nombre_empleado = $empleado ? $empleado->nombre : 'Empleado no encontrado';

    }
}
