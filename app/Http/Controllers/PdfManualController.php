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

    public function presupuestoCompra()
    {
        return Pdf::loadView('pdf.manual-usuario.presupuesto-compra')
            ->setPaper('A4', 'portrait')
            ->download('manual-usuario-presupuesto-compra.pdf');
    }

    public function pais()
    {
        return Pdf::loadView('pdf.manual-usuario.pais')
            ->setPaper('A4', 'portrait')
            ->download('manual-usuario-paises.pdf');
    }

    public function proveedor()
    {
        return Pdf::loadView('pdf.manual-usuario.proveedor')
            ->setPaper('A4', 'portrait')
            ->download('manual-usuario-proveedores.pdf');
    }

    public function marca()
    {
        return Pdf::loadView('pdf.manual-usuario.marca')
            ->setPaper('A4', 'portrait')
            ->download('manual-usuario-marcas.pdf');
    }

    public function modelo()
    {
        return Pdf::loadView('pdf.manual-usuario.modelo')
            ->setPaper('A4', 'portrait')
            ->download('manual-usuario-modelos.pdf');
    }

    public function articulo()
    {
        return Pdf::loadView('pdf.manual-usuario.articulo')
            ->setPaper('A4', 'portrait')
            ->download('manual-usuario-articulos.pdf');
    }
}
