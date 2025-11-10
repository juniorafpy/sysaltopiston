<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Timbrado extends Model
{
    use HasFactory;

    protected $table = 'timbrados';
    protected $primaryKey = 'cod_timbrado';

    protected $fillable = [
        'numero_timbrado',
        'fecha_inicio_vigencia',
        'fecha_fin_vigencia',
        'numero_inicial',
        'numero_final',
        'numero_actual',
        'establecimiento',
        'punto_expedicion',
        'activo'
    ];

    protected $casts = [
        'fecha_inicio_vigencia' => 'date',
        'fecha_fin_vigencia' => 'date',
        'activo' => 'boolean'
    ];

    /**
     * Relación con facturas
     */
    public function facturas()
    {
        return $this->hasMany(Factura::class, 'cod_timbrado', 'cod_timbrado');
    }

    /**
     * Verifica si el timbrado está vigente en una fecha
     */
    public function estaVigente(?Carbon $fecha = null): bool
    {
        $fecha = $fecha ?? Carbon::now();

        return $this->activo
            && $fecha->between($this->fecha_inicio_vigencia, $this->fecha_fin_vigencia)
            && intval($this->numero_actual) <= intval($this->numero_final);
    }

    /**
     * Obtiene el siguiente número de factura disponible
     */
    public function obtenerSiguienteNumero(): string
    {
        if (!$this->estaVigente()) {
            throw new \Exception('El timbrado no está vigente o no tiene números disponibles.');
        }

        $numeroActual = intval($this->numero_actual);
        $numeroFinal = intval($this->numero_final);

        if ($numeroActual > $numeroFinal) {
            throw new \Exception('El timbrado ha alcanzado su número final.');
        }

        return str_pad($numeroActual, 7, '0', STR_PAD_LEFT);
    }

    /**
     * Incrementa el número actual después de generar una factura
     */
    public function incrementarNumeroActual(): void
    {
        $numeroActual = intval($this->numero_actual);
        $this->numero_actual = str_pad($numeroActual + 1, 7, '0', STR_PAD_LEFT);
        $this->save();
    }

    /**
     * Formatea el número de factura completo
     */
    public function formatearNumeroFactura(string $numeroFactura): string
    {
        return "{$this->establecimiento}-{$this->punto_expedicion}-{$numeroFactura}";
    }

    /**
     * Scope para obtener timbrados activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para obtener timbrados vigentes
     */
    public function scopeVigentes($query)
    {
        $hoy = Carbon::now()->toDateString();
        return $query->where('activo', true)
                     ->where('fecha_inicio_vigencia', '<=', $hoy)
                     ->where('fecha_fin_vigencia', '>=', $hoy)
                     ->whereRaw("CAST(numero_actual AS INTEGER) <= CAST(numero_final AS INTEGER)");
    }

    /**
     * Accessor para mostrar el estado de vigencia
     */
    public function getEstadoVigenciaAttribute(): string
    {
        if (!$this->activo) {
            return 'Inactivo';
        }

        $hoy = Carbon::now();

        if ($hoy->lt($this->fecha_inicio_vigencia)) {
            return 'Pendiente';
        }

        if ($hoy->gt($this->fecha_fin_vigencia)) {
            return 'Vencido';
        }

        if (intval($this->numero_actual) > intval($this->numero_final)) {
            return 'Agotado';
        }

        return 'Vigente';
    }

    /**
     * Accessor para números disponibles
     */
    public function getNumerosDisponiblesAttribute(): int
    {
        return max(0, intval($this->numero_final) - intval($this->numero_actual) + 1);
    }
}
