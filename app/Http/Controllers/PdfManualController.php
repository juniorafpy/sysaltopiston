<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfManualController extends Controller
{
    public function pedidoCompra()
    {
        return Pdf::loadView('pdf.manual-pedido-compra')
            ->setPaper('A4', 'portrait')
            ->download('manual-pedido-compra.pdf');
    }
}
