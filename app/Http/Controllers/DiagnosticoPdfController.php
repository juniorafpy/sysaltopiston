<?php

namespace App\Http\Controllers;

use App\Models\Diagnostico;
use Illuminate\Support\Facades\Log;

class DiagnosticoPdfController extends Controller
{
    /**
     * Genera y muestra un PDF para un diagnóstico específico.
     *
     * @param  \App\Models\Diagnostico  $record
     * @return \Illuminate\Http\Response
     */
    public function imprimir(Diagnostico $record)
    {
        // Cargar las relaciones necesarias para la vista de una manera más eficiente

        $record->load([
            'recepcionVehiculo.vehiculo.marca',
            'recepcionVehiculo.vehiculo.modelo',
            'recepcionVehiculo.cliente']);

        // Renderizar la vista Blade a HTML
        $html = view('pdf.diagnostico', ['diagnostico' => $record])->render();

        // Cargar Dompdf dinámicamente para evitar errores si aún no está instalado
        $optionsClass = '\\Dompdf\\Options';
        $dompdfClass = '\\Dompdf\\Dompdf';

        if (!class_exists($optionsClass) || !class_exists($dompdfClass)) {
            Log::error('Dompdf\Dompdf no disponible. Instale la dependencia dompdf/dompdf:^2.0 antes de generar PDF.');
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

        $filename = 'diagnostico-' . $record->id . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }
}
