<?php

namespace App\Http\Controllers;

use App\Models\RecepcionVehiculo;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class RecepcionPdfController extends Controller
{
    public function generarComprobante($id)
    {
        $recepcion = RecepcionVehiculo::with([
            'cliente.persona',
            'vehiculo.modelo.marca',
            'vehiculo.color',
            'empleado.persona',
            'inventario'
        ])->findOrFail($id);

        $html = view('pdf.comprobante-recepcion', compact('recepcion'))->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'Arial');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->stream("Comprobante-Recepcion-{$recepcion->id}.pdf", ["Attachment" => false]);
    }
}
