<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecepcionInventario extends Model
{
    use HasFactory;

    protected $table = 'recepcion_inventarios';
        public $timestamps = false;

    protected $fillable = [
        'recepcion_vehiculo_id',
        'extintor',
        'valija',
        'rueda_auxilio',
        'gato',
        'llave_ruedas',
        'triangulos_seguridad',
        'botiquin',
        'manual_vehiculo',
        'llave_repuesto',
        'radio_estereo',
        'nivel_combustible',
        'observaciones_inventario',
    ];

    protected $casts = [
        'extintor' => 'boolean',
        'valija' => 'boolean',
        'rueda_auxilio' => 'boolean',
        'gato' => 'boolean',
        'llave_ruedas' => 'boolean',
        'triangulos_seguridad' => 'boolean',
        'botiquin' => 'boolean',
        'manual_vehiculo' => 'boolean',
        'llave_repuesto' => 'boolean',
        'radio_estereo' => 'boolean',
    ];

    public function recepcionVehiculo()
    {
        return $this->belongsTo(RecepcionVehiculo::class, 'recepcion_vehiculo_id', 'id');
    }

    /**
     * Obtiene un array con los artículos inventariados y su estado
     */
    public function getInventarioArray(): array
    {
        return [
            'Extintor' => $this->extintor,
            'Valija/Maletero' => $this->valija,
            'Rueda de auxilio' => $this->rueda_auxilio,
            'Gato' => $this->gato,
            'Llave de ruedas' => $this->llave_ruedas,
            'Triángulos de seguridad' => $this->triangulos_seguridad,
            'Botiquín' => $this->botiquin,
            'Manual del vehículo' => $this->manual_vehiculo,
            'Llave repuesto' => $this->llave_repuesto,
            'Radio/Estéreo' => $this->radio_estereo,
        ];
    }

    /**
     * Cuenta cuántos artículos están presentes
     */
    public function getArticulosPresentesCount(): int
    {
        return collect($this->getInventarioArray())->filter()->count();
    }
}
