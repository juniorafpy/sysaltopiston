<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class OrdenCompraCabecera extends Model
{
    use HasFactory;

    protected $table = 'orden_compra_cabecera'; //definicion de la tabla

    protected $primaryKey = 'nro_orden_compra'; // Clave primaria

    public $timestamps = false;

    protected $fillable = [
       'fec_orden',
       'nro_presupuesto_ref',
       'fec_entrega',
       'cod_proveedor',
       'cod_condicion_compra',
       'cod_sucursal',
       'estado',
       'observacion',
       'monto_gravado',
       'monto_tot_impuesto',
       'monto_general',
       'usuario_alta',
       'fec_alta'
    ]; //campos para visualizar

    protected $casts = [
        'fec_orden' => 'date',
        'fec_entrega' => 'date',
        'fec_alta' => 'datetime',
    ];

     public function ordenCompraDetalles()
{
    return $this->hasMany(OrdenCompraDetalle::class, 'nro_orden_compra', 'nro_orden_compra');
}

     public function sucursale(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class, 'cod_proveedor');
    }

    public function condicionCompra()
    {
        return $this->belongsTo(CondicionCompra::class, 'cod_condicion_compra', 'cod_condicion');
    }

    public function estadoRel()
    {
        return $this->belongsTo(\App\Models\Estados::class, 'estado');
    }

    public function usuarioAlta()
    {
        return $this->belongsTo(\App\Models\User::class, 'usuario_alta', 'name');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            $model->usuario_alta = Auth::user()->name;
            $model->fec_alta = now();
            $model->cod_sucursal = Auth::user()->cod_sucursal;
            $model->estado = $model->estado ?? 'PENDIENTE';
        });
    }

    /**
     * Genera el PDF de la orden de compra
     *
     * @param string $modo 'download' para descargar, 'stream' para visualizar en navegador
     * @return \Illuminate\Http\Response
     */
    public function generarPDF(string $modo = 'stream')
    {
        try {
            \Log::info('Iniciando generación PDF para orden: ' . $this->nro_orden_compra);

            // Cargar todas las relaciones necesarias
            $this->load([
                'ordenCompraDetalles.articulo',
                'proveedor.personas_pro',
                'condicionCompra',
                'sucursale',
                'estadoRel'
            ]);

            \Log::info('Relaciones cargadas correctamente');

            // Usar montos guardados; si están en 0 calcular de los detalles
            $montoGravado  = $this->monto_gravado    ?: ($this->ordenCompraDetalles->sum('total') - $this->ordenCompraDetalles->sum('total_iva'));
            $montoImpuesto = $this->monto_tot_impuesto ?: $this->ordenCompraDetalles->sum('total_iva');
            $montoGeneral  = $this->monto_general    ?: $this->ordenCompraDetalles->sum('total');

            // Condición: CREDITO si dias_cuotas > 0
            $condicion = $this->condicionCompra;
            $condicionLabel = ($condicion && (int)$condicion->dias_cuotas > 0)
                ? 'CREDITO — ' . $condicion->descripcion
                : ($condicion?->descripcion ?? 'CONTADO');

            // Renderizar la vista Blade a HTML
            $html = view('pdf.orden-compra', [
                'ordenCompra'    => $this,
                'montoGravado'   => $montoGravado,
                'montoImpuesto'  => $montoImpuesto,
                'montoGeneral'   => $montoGeneral,
                'condicionLabel' => $condicionLabel,
            ])->render();

            \Log::info('Vista Blade renderizada. Longitud HTML: ' . strlen($html));

            // Verificar que Dompdf esté disponible
            if (!class_exists('\Dompdf\Dompdf')) {
                \Log::error('Dompdf no está instalado');
                abort(500, 'Dompdf no está instalado. Ejecute: composer require dompdf/dompdf');
            }

            // Configurar Dompdf
            $options = new \Dompdf\Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');

            \Log::info('Creando instancia Dompdf');
            $dompdf = new \Dompdf\Dompdf($options);

            \Log::info('Cargando HTML en Dompdf');
            $dompdf->loadHtml($html);

            \Log::info('Configurando papel A4');
            $dompdf->setPaper('A4', 'portrait');

            \Log::info('Renderizando PDF');
            $dompdf->render();

            // Nombre del archivo
            $filename = 'orden-compra-' . $this->nro_orden_compra . '.pdf';

            \Log::info('PDF generado exitosamente: ' . $filename);

            // Retornar según el modo
            return response($dompdf->output(), 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', ($modo === 'stream' ? 'inline' : 'attachment') . '; filename="' . $filename . '"');

        } catch (\Exception $e) {
            \Log::error('Error generando PDF de orden de compra: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            abort(500, 'Error generando PDF: ' . $e->getMessage());
        }
    }

}
