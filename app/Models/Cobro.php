<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Cobro extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'cobros';
    protected $primaryKey = 'cod_cobro';

    protected $fillable = [
        'cod_cliente',
        'cod_apertura',
        'fecha_cobro',
        'monto_total',
        'estado',
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
                        'cod_forma_cobro' => $formaPago['cod_forma_cobro'] ?? null,
                        'cod_entidad_bancaria' => $formaPago['cod_entidad_bancaria'] ?? null,
                        'cod_tipo_tarjeta' => $formaPago['cod_tipo_tarjeta'] ?? null,
                        'cod_procesadora' => $formaPago['cod_procesadora'] ?? null,
                        'numero_voucher' => $formaPago['numero_voucher'] ?? null,
                        'numero_cheque' => $formaPago['numero_cheque'] ?? null,
                    ]);
                }
            }

            return $cobro->fresh(['detalles', 'formasPago', 'cliente']);
        });
    }
}
