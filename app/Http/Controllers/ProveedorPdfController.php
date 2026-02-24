<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Support\Facades\Log;

class ProveedorPdfController extends Controller
{
    public function imprimir()
    {
        $proveedores = Proveedor::with('personas_pro')
            ->orderByDesc('cod_proveedor')
            ->get();

        $html = view('pdf.proveedores', [
            'proveedores' => $proveedores,
            'fecha' => now(),
        ])->render();

        $optionsClass = '\\Dompdf\\Options';
        $dompdfClass = '\\Dompdf\\Dompdf';

        if (!class_exists($optionsClass) || !class_exists($dompdfClass)) {
            Log::error('Dompdf no disponible. Instale dompdf/dompdf:^2.0 para generar PDF de proveedores.');
            abort(500, 'Dompdf no está instalado.');
        }

        $options = new $optionsClass();
        if (method_exists($options, 'set')) {
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');
        }

        $dompdf = new $dompdfClass($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'proveedores-' . now()->format('Ymd-His') . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }
}
