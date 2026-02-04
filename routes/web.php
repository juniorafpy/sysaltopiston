<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DiagnosticoPdfController;
use App\Http\Controllers\OrdenCompraPDFController;
use App\Models\OrdenServicio;

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
    return $ordenServicio->generarPDF('stream');
})->name('orden-servicio.pdf')->middleware(['auth']);

// PDF de orden de compra
Route::get('/orden-compra/{ordenCompra}/pdf', function (\App\Models\OrdenCompraCabecera $ordenCompra) {
    return $ordenCompra->generarPDF('stream');
})->name('orden-compra.pdf')->middleware(['auth']);
