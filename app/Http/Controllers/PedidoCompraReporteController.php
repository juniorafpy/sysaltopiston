<?php

namespace App\Http\Controllers;

use App\Models\PedidoCabeceras;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PedidoCompraReporteController extends Controller
{
    public function pdf(Request $request)
    {
        $fechaDesde = $request->string('fecha_desde')->toString();
        $fechaHasta = $request->string('fecha_hasta')->toString();
        $estado = $request->string('estado')->toString();

        $query = PedidoCabeceras::query()
            ->with(['ped_empleados.persona', 'sucursal_ped'])
            ->orderByDesc('fec_pedido');

        if (!empty($fechaDesde)) {
            $query->whereDate('fec_pedido', '>=', $fechaDesde);
        }

        if (!empty($fechaHasta)) {
            $query->whereDate('fec_pedido', '<=', $fechaHasta);
        }

        if (!empty($estado) && $estado !== 'TODOS') {
            $query->where('estado', $estado);
        }

        $pedidos = $query->get();

        $html = view('pdf.reporte-pedidos-compra', [
            'pedidos' => $pedidos,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'estado' => $estado,
            'fechaGeneracion' => now(),
        ])->render();

        $optionsClass = '\\Dompdf\\Options';
        $dompdfClass = '\\Dompdf\\Dompdf';

        if (!class_exists($optionsClass) || !class_exists($dompdfClass)) {
            Log::error('Dompdf no disponible. Instale dompdf/dompdf:^2.0 para generar reportes de pedidos de compra.');
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

        $filename = 'reporte-pedidos-compra-' . now()->format('Ymd-His') . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    }
}
