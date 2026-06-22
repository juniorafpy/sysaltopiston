<?php

namespace App\Http\Controllers;

use App\Models\PresupuestoCabecera;
use App\Models\PresupuestoVenta;

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

    public function imprimirVenta(PresupuestoVenta $presupuesto)
    {
        $presupuesto->load([
            'detalles.articulo',
            'cliente.persona',
            'diagnostico.recepcionVehiculo.vehiculo.modelo',
            'condicion',
            'sucursal',
            'tipoVenta',
        ]);

        $subtotal = $presupuesto->detalles->sum(fn($d) => (float)$d->subtotal);
        $totalIva = $presupuesto->detalles->sum(fn($d) => (float)$d->monto_impuesto);
        $totalDescuento = $presupuesto->detalles->sum(fn($d) => (float)$d->monto_descuento);
        $total = $presupuesto->detalles->sum(fn($d) => (float)$d->total);

        $html = view('pdf.presupuesto-venta', compact('presupuesto', 'subtotal', 'totalIva', 'totalDescuento', 'total'))->render();

        $optionsClass = '\\Dompdf\\Options';
        $dompdfClass  = '\\Dompdf\\Dompdf';

        $options = new $optionsClass();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new $dompdfClass($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'presupuesto-venta-' . $presupuesto->id . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }
}
