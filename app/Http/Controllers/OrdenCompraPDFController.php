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
            'estadoRel'
        ]);

        // Calcular totales
        $subtotal = $ordenCompra->ordenCompraDetalles->sum('total');
        $totalIva = $ordenCompra->ordenCompraDetalles->sum('total_iva');
        $total = $subtotal + $totalIva;

        $data = [
            'ordenCompra' => $ordenCompra,
            'subtotal' => $subtotal,
            'totalIva' => $totalIva,
            'total' => $total,
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
