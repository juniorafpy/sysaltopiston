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
        'cod_timbrado_recibo',
        'numero_recibo',
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

    public function timbradoRecibo()
    {
        return $this->belongsTo(Timbrado::class, 'cod_timbrado_recibo', 'cod_timbrado');
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
     * Determina si el cobro corresponde al pago de al menos una factura de crédito
     */
    public function esPagoCredito(): bool
    {
        $this->loadMissing(['detalles.factura']);

        foreach ($this->detalles as $detalle) {
            if ($detalle->factura && $detalle->factura->condicion_venta === 'Crédito') {
                return true;
            }
        }

        return false;
    }

    /**
     * Genera el PDF del recibo de cobro
     */
    public function generarReciboPDF(string $modo = 'stream')
    {
        $this->load(['cliente', 'timbradoRecibo', 'detalles.factura', 'formasPago.entidadBancaria', 'formasPago.tipoTarjeta', 'formasPago.formaCobro']);

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $html = view('pdf.recibo-cobro', ['cobro' => $this])->render();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('letter', 'portrait');
        $dompdf->render();

        $filename = sprintf('Recibo_Cobro_%s_%s.pdf', str_pad($this->cod_cobro, 6, '0', STR_PAD_LEFT), now()->format('Ymd_His'));

        $disposition = $modo === 'stream' ? 'inline' : 'attachment';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', $disposition . '; filename="' . $filename . '"');
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
