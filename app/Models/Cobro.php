<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Cobro extends Model
{
    use HasFactory;

    protected $table = 'cobros';
    protected $primaryKey = 'cod_cobro';

    protected $fillable = [
        'cod_cliente',
        'cod_apertura',
        'fecha_cobro',
        'monto_total',
        'observaciones',
        'usuario_alta',
        'fecha_alta'
    ];

    protected $casts = [
        'fecha_cobro' => 'date',
        'monto_total' => 'decimal:2',
        'fecha_alta' => 'datetime'
    ];

    /**
     * Relaciones
     */
    public function cliente()
    {
        return $this->belongsTo(Personas::class, 'cod_cliente', 'cod_persona');
    }

    public function aperturaCaja()
    {
        return $this->belongsTo(AperturaCaja::class, 'cod_apertura', 'cod_apertura');
    }

    public function detalles()
    {
        return $this->hasMany(CobroDetalle::class, 'cod_cobro', 'cod_cobro');
    }

    public function formasPago()
    {
        return $this->hasMany(CobroFormaPago::class, 'cod_cobro', 'cod_cobro');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_alta', 'id');
    }

    /**
     * Boot del modelo
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cobro) {
            if (!$cobro->usuario_alta) {
                $cobro->usuario_alta = Auth::id();
            }
            if (!$cobro->fecha_alta) {
                $cobro->fecha_alta = now();
            }
        });

        static::created(function ($cobro) {
            // Registrar el cobro en movimientos de caja
            $cobro->registrarMovimientoCaja();
        });
    }

    /**
     * Registra el cobro como ingreso en caja
     */
    public function registrarMovimientoCaja(): void
    {
        $detalleFacturas = $this->detalles()
            ->with('factura')
            ->get()
            ->map(function ($detalle) {
                return "Factura {$detalle->factura->numero_factura} - Cuota {$detalle->numero_cuota}";
            })
            ->join(', ');

        MovimientoCaja::create([
            'cod_apertura' => $this->cod_apertura,
            'tipo_movimiento' => 'Ingreso',
            'concepto' => "Cobro N° {$this->cod_cobro} - {$detalleFacturas}",
            'monto' => $this->monto_total,
            'fecha_movimiento' => now(),
            'usuario_alta' => $this->usuario_alta,
            'fecha_alta' => now()
        ]);
    }

    /**
     * Crea un cobro completo con sus detalles y formas de pago
     */
    public static function crearCobroCompleto(array $data): self
    {
        return DB::transaction(function () use ($data) {
            // 1. Crear la cabecera del cobro
            $cobro = self::create([
                'cod_cliente' => $data['cod_cliente'],
                'cod_apertura' => $data['cod_apertura'],
                'fecha_cobro' => $data['fecha_cobro'],
                'monto_total' => $data['monto_total'],
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            // 2. Crear los detalles (facturas/cuotas)
            if (isset($data['detalles'])) {
                foreach ($data['detalles'] as $detalle) {
                    CobroDetalle::create([
                        'cod_cobro' => $cobro->cod_cobro,
                        'cod_factura' => $detalle['cod_factura'],
                        'numero_cuota' => $detalle['numero_cuota'],
                        'monto_cuota' => $detalle['monto_cuota'],
                    ]);
                }
            }

            // 3. Crear las formas de pago
            if (isset($data['formas_pago'])) {
                foreach ($data['formas_pago'] as $formaPago) {
                    CobroFormaPago::create([
                        'cod_cobro' => $cobro->cod_cobro,
                        'tipo_transaccion' => $formaPago['tipo_transaccion'],
                        'monto' => $formaPago['monto'],
                        'cod_entidad_bancaria' => $formaPago['cod_entidad_bancaria'] ?? null,
                        'numero_voucher' => $formaPago['numero_voucher'] ?? null,
                        'numero_cheque' => $formaPago['numero_cheque'] ?? null,
                    ]);
                }
            }

            return $cobro->fresh(['detalles', 'formasPago', 'cliente']);
        });
    }
}
