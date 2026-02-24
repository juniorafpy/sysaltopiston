<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiagnosticoPdfController;
use App\Http\Controllers\PedidoCompraReporteController;
use App\Http\Controllers\ProveedorPdfController;
use App\Http\Controllers\RecepcionPdfController;
use App\Http\Controllers\RecepcionVehiculoReporteController;
use App\Models\OrdenServicio;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// PDF de diagnóstico
Route::get('/diagnosticos/{record}/pdf', [DiagnosticoPdfController::class, 'imprimir'])
    ->name('diagnosticos.pdf');
// Alias compatible (si existía en la UI)
Route::get('/diagnosticos/{record}/imprimir', [DiagnosticoPdfController::class, 'imprimir'])
    ->name('diagnosticos.imprimir');

// PDF de orden de servicio
Route::get('/orden-servicio/{ordenServicio}/pdf', function (OrdenServicio $ordenServicio) {
    if (!in_array($ordenServicio->estado_trabajo, ['En Proceso', 'Finalizado'])) {
        $ordenServicio->update([
            'estado_trabajo' => 'En Proceso',
        ]);
    }

    return $ordenServicio->generarPDF('stream');
})->name('orden-servicio.pdf')->middleware(['auth']);

// PDF de orden de compra
Route::get('/orden-compra/{ordenCompra}/pdf', function (\App\Models\OrdenCompraCabecera $ordenCompra) {
    return $ordenCompra->generarPDF('stream');
})->name('orden-compra.pdf')->middleware(['auth']);

// PDF de comprobante de recepción
Route::get('/recepcion-vehiculo/{id}/pdf', [RecepcionPdfController::class, 'generarComprobante'])
    ->name('recepcion-vehiculo.pdf')
    ->middleware(['auth']);

// PDF de listado de proveedores
Route::get('/proveedores/pdf', [ProveedorPdfController::class, 'imprimir'])
    ->name('proveedores.pdf')
    ->middleware(['auth']);

// PDF de reporte de pedidos de compra
Route::get('/informes/pedidos-compra/pdf', [PedidoCompraReporteController::class, 'pdf'])
    ->name('informes.pedidos-compra.pdf')
    ->middleware(['auth']);

// PDF de reporte de recepciones de vehículos
Route::get('/informes/recepciones-vehiculos/pdf', [RecepcionVehiculoReporteController::class, 'pdf'])
    ->name('informes.recepciones-vehiculos.pdf')
    ->middleware(['auth']);

// Manual de usuario del módulo Compras (PDF)
Route::get('/ayuda/compras/manual.pdf', function (Request $request) {
    $rutaManual = base_path('MANUAL_USUARIO_MODULO_COMPRAS.md');

    if (!file_exists($rutaManual)) {
        abort(404, 'No se encontró el manual de compras.');
    }

    $markdown = file_get_contents($rutaManual) ?: '';
    $markdown = mb_convert_encoding($markdown, 'UTF-8', 'UTF-8');
    $contenidoHtml = Str::markdown($markdown);

    $html = view('pdf.manual-compras', [
        'contenidoHtml' => $contenidoHtml,
    ])->render();

    $options = new \Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', true);

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $filename = 'Manual_Usuario_Modulo_Compras.pdf';
    $disposition = $request->boolean('download') ? 'attachment' : 'inline';

    return response($dompdf->output(), 200)
        ->header('Content-Type', 'application/pdf')
        ->header('Content-Disposition', $disposition . '; filename="' . $filename . '"');
})->name('ayuda.compras.pdf')->middleware(['auth']);
