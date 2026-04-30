<?php

namespace App\Http\Controllers;

use App\Models\OrdenCompraCabecera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OrdenCompraPDFController extends Controller
{
    public function generarPDF(OrdenCompraCabecera $ordenCompra)
    {
        // Cargar las relaciones necesarias
        $ordenCompra->load([
            'ordenCompraDetalles.articulo',
            'proveedor.personas_pro',
            'condicionCompra',
            'sucursale',
        ]);

        // Usar montos guardados en la cabecera; si están vacíos calcular de los detalles
        $montoGravado    = $ordenCompra->monto_gravado    ?: ($ordenCompra->ordenCompraDetalles->sum('total') - $ordenCompra->ordenCompraDetalles->sum('total_iva'));
        $montoImpuesto   = $ordenCompra->monto_tot_impuesto ?: $ordenCompra->ordenCompraDetalles->sum('total_iva');
        $montoGeneral    = $ordenCompra->monto_general    ?: $ordenCompra->ordenCompraDetalles->sum('total');

        // Condición de compra: mostrar CREDITO si dias_cuotas > 0
        $condicion       = $ordenCompra->condicionCompra;
        $condicionLabel  = ($condicion && (int)$condicion->dias_cuotas > 0)
            ? 'CREDITO — ' . $condicion->descripcion
            : ($condicion?->descripcion ?? 'CONTADO');

        $data = [
            'ordenCompra'    => $ordenCompra,
            'montoGravado'   => $montoGravado,
            'montoImpuesto'  => $montoImpuesto,
            'montoGeneral'   => $montoGeneral,
            'condicionLabel' => $condicionLabel,
        ];

        // Renderizar la vista Blade a HTML
        $html = view('pdf.orden-compra', $data)->render();

        // Cargar Dompdf dinámicamente
        $optionsClass = '\\Dompdf\\Options';
        $dompdfClass = '\\Dompdf\\Dompdf';

        if (!class_exists($optionsClass) || !class_exists($dompdfClass)) {
            Log::error('Dompdf no disponible. Instale la dependencia dompdf/dompdf:^2.0 antes de generar PDF.');
            abort(500, "Dompdf no está instalado. Ejecute: composer require \"dompdf/dompdf:^2.0\"");
        }

        // Configurar Dompdf
        $options = new $optionsClass();
        if (method_exists($options, 'set')) {
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
        }

        $dompdf = new $dompdfClass($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'orden-compra-' . $ordenCompra->nro_orden_compra . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
