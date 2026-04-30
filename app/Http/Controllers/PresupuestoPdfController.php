<?php

namespace App\Http\Controllers;

use App\Models\PresupuestoCabecera;

class PresupuestoPdfController extends Controller
{
    public function imprimir(PresupuestoCabecera $presupuesto)
    {
        $presupuesto->load([
            'presupuestoDetalles.articulo',
            'proveedor.personas_pro',
            'condicionCompra',
        ]);

        $subtotal  = $presupuesto->presupuestoDetalles->sum(fn($d) => (float)$d->total - (float)$d->total_iva);
        $totalIva  = $presupuesto->presupuestoDetalles->sum(fn($d) => (float)$d->total_iva);
        $total     = $presupuesto->presupuestoDetalles->sum(fn($d) => (float)$d->total);

        $html = view('pdf.presupuesto', compact('presupuesto', 'subtotal', 'totalIva', 'total'))->render();

        $optionsClass = '\\Dompdf\\Options';
        $dompdfClass  = '\\Dompdf\\Dompdf';

        $options = new $optionsClass();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new $dompdfClass($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'presupuesto-' . $presupuesto->nro_presupuesto . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }
}
