<?php

namespace App\Http\Controllers;

use App\Models\RecepcionVehiculo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RecepcionVehiculoReporteController extends Controller
{
    public function pdf(Request $request)
    {
        $fechaDesde = $request->string('rv_fecha_desde')->toString();
        $fechaHasta = $request->string('rv_fecha_hasta')->toString();
        $estado = $request->string('rv_estado')->toString();

        $query = RecepcionVehiculo::query()
            ->with(['cliente.persona', 'vehiculo.modelo', 'empleado.persona'])
            ->orderByDesc('fecha_recepcion');

        if (!empty($fechaDesde)) {
            $query->whereDate('fecha_recepcion', '>=', $fechaDesde);
        }

        if (!empty($fechaHasta)) {
            $query->whereDate('fecha_recepcion', '<=', $fechaHasta);
        }

        if (!empty($estado) && $estado !== 'TODOS') {
            $query->where('estado', $estado);
        }

        $recepciones = $query->get();

        $html = view('pdf.reporte-recepciones-vehiculos', [
            'recepciones' => $recepciones,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'estado' => $estado,
            'fechaGeneracion' => now(),
        ])->render();

        $optionsClass = '\\Dompdf\\Options';
        $dompdfClass = '\\Dompdf\\Dompdf';

        if (!class_exists($optionsClass) || !class_exists($dompdfClass)) {
            Log::error('Dompdf no disponible. Instale dompdf/dompdf:^2.0 para generar reportes de recepción de vehículos.');
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

        $filename = 'reporte-recepciones-vehiculos-' . now()->format('Ymd-His') . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }
}
