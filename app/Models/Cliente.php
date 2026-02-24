<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';
    protected $primaryKey = 'cod_cliente';
    public $timestamps = false;

    protected $fillable = [
        'cod_persona',
        'estado',
        'usuario_alta',
        'fec_alta',
    ];

    /**
     * Relación con Personas
     */
    public function persona()
    {
        return $this->belongsTo(Personas::class, 'cod_persona', 'cod_persona');
    }

    /**
     * Relación con Facturas
     */
    public function facturas()
    {
        return $this->hasMany(Factura::class, 'cod_cliente', 'cod_cliente');
    }

    /**
     * Relación con Vehículos
     */
    public function vehiculos()
    {
        return $this->hasMany(Vehiculo::class, 'cliente_id', 'cod_cliente');
    }

    /**
     * Accessor para obtener el nombre completo del cliente
     */
    public function getNombreCompletoAttribute()
    {
        if (!$this->persona) {
            return '';
        }

        if ($this->persona->razon_social) {
            return $this->persona->razon_social;
        }

        return trim($this->persona->nombres . ' ' . $this->persona->apellidos);
    }

    /**
     * Accessor para verificar si está activo
     */
    public function getIsActivoAttribute()
    {
        return $this->estado === 'A';
    }
}
