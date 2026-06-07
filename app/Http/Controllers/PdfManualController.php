<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;

class PdfManualController extends Controller
{
    public function pedidoCompra()
    {
        return Pdf::loadView('pdf.manual-usuario.pedido-compra')
            ->setPaper('A4', 'portrait')
            ->download('manual-usuario-pedido-compra.pdf');
    }
}
