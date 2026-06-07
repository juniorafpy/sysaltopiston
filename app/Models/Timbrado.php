<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Timbrado extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'timbrados';
    protected $primaryKey = 'cod_timbrado';

    protected $fillable = [
        'numero_timbrado',
        'tipo_comprobante',
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
     * Obtiene el timbrado activo y vigente para el usuario autenticado
     * 
     * Prioridad:
     * 1. Caja(s) de la sucursal del usuario -> caja_timbrado
     * 2. Sucursal del usuario -> establecimiento
     * 3. Cualquier timbrado activo/vigente
     */
    public static function obtenerTimbradoActivo(?string $tipoComprobante = null): ?self
    {
        $user = auth()->user();
        
        if (!$user) {
            return null;
        }

        // 1. Buscar timbrado desde cajas de la sucursal del usuario (vía caja_timbrado)
        $timbrado = self::obtenerTimbradoDesdeCajasSucursal($user, $tipoComprobante);
        if ($timbrado) {
            return $timbrado;
        }

        // 2. Buscar por establecimiento de la sucursal del usuario
        $timbrado = self::obtenerTimbradoPorEstablecimiento($user, $tipoComprobante);
        if ($timbrado) {
            return $timbrado;
        }

        // 3. Último recurso: cualquier timbrado activo/vigente
        $timbrado = self::obtenerCualquierTimbradoActivo($tipoComprobante);
        if ($timbrado) {
            return $timbrado;
        }

        return null;
    }

    /**
     * Busca timbrados activos asignados a cajas de la sucursal del usuario
     */
    private static function obtenerTimbradoDesdeCajasSucursal($user, ?string $tipoComprobante = null): ?self
    {
        if (!$user->cod_sucursal) {
            return null;
        }

        // Obtener todas las cajas de la sucursal
        $cajas = \App\Models\Caja::where('cod_sucursal', $user->cod_sucursal)
            ->where('activo', true)
            ->pluck('cod_caja');

        if ($cajas->isEmpty()) {
            return null;
        }

        // Buscar timbrados activos asignados a esas cajas
        $cajaTimbrados = \App\Models\CajaTimbrado::whereIn('cod_caja', $cajas)
            ->where('activo', true)
            ->with('timbrado')
            ->get();

        foreach ($cajaTimbrados as $ct) {
            if ($ct->timbrado && $ct->timbrado->estaVigente()) {
                if ($tipoComprobante && $ct->timbrado->tipo_comprobante !== $tipoComprobante) {
                    continue;
                }
                return $ct->timbrado;
            }
        }

        return null;
    }

    /**
     * Busca timbrados por establecimiento de la sucursal
     */
    private static function obtenerTimbradoPorEstablecimiento($user, ?string $tipoComprobante = null): ?self
    {
        $query = self::where('activo', true)
            ->where('fecha_inicio_vigencia', '<=', now())
            ->where('fecha_fin_vigencia', '>=', now());

        if ($tipoComprobante) {
            $query->where('tipo_comprobante', $tipoComprobante);
        }

        if ($user->cod_sucursal) {
            $sucursal = $user->sucursal;
            if ($sucursal && !empty($sucursal->establecimiento)) {
                $query->where('establecimiento', (string) $sucursal->establecimiento);
            }
        }

        return self::filtrarPrimeroConNumeros($query);
    }

    /**
     * Último recurso: cualquier timbrado activo/vigente
     */
    private static function obtenerCualquierTimbradoActivo(?string $tipoComprobante = null): ?self
    {
        $query = self::where('activo', true)
            ->where('fecha_inicio_vigencia', '<=', now())
            ->where('fecha_fin_vigencia', '>=', now());

        if ($tipoComprobante) {
            $query->where('tipo_comprobante', $tipoComprobante);
        }

        return self::filtrarPrimeroConNumeros($query);
    }

    /**
     * Filtra candidatos en PHP y retorna el primero con números disponibles
     */
    private static function filtrarPrimeroConNumeros($query): ?self
    {
        $candidates = $query->orderBy('fecha_fin_vigencia', 'desc')->get();

        foreach ($candidates as $t) {
            $actual = (int) preg_replace('/[^0-9]/', '', $t->numero_actual ?? '');
            $final = (int) preg_replace('/[^0-9]/', '', $t->numero_final ?? '');
            
            if ($actual > 0 && $final > 0 && $actual <= $final) {
                return $t;
            }
        }

        return null;
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
