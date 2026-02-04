<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Probando cargar formulario de cobros...\n";
echo str_repeat("=", 60) . "\n\n";

try {
    // Simular lo que hace CobroResource al cargar el formulario
    echo "1. Cargando opciones de clientes...\n";
    
    $clientes = \App\Models\Personas::whereHas('facturas', function ($query) {
        $query->where('condicion_venta', 'Crédito')->where('estado', 'Emitida');
    })
    ->get()
    ->filter(function ($cliente) {
        return $cliente->facturas()
            ->where('condicion_venta', 'Crédito')
            ->where('estado', 'Emitida')
            ->get()
            ->filter(fn ($factura) => $factura->getSaldoConNotas() > 0)
            ->count() > 0;
    });
    
    echo "   ✓ Clientes con saldo: " . $clientes->count() . "\n\n";
    
    // Probar con el primer cliente
    if ($clientes->count() > 0) {
        $cliente = $clientes->first();
        echo "2. Probando cliente: {$cliente->nombre_completo}\n";
        
        $facturas = \App\Models\Factura::where('cod_cliente', $cliente->cod_persona)
            ->where('condicion_venta', 'Crédito')
            ->where('estado', 'Emitida')
            ->with('condicionCompra')
            ->get()
            ->filter(function ($factura) {
                return $factura->getSaldoConNotas() > 0;
            });
        
        echo "   Facturas con saldo: " . $facturas->count() . "\n";
        
        foreach ($facturas as $factura) {
            $saldo = $factura->getSaldoConNotas();
            echo "   - {$factura->numero_factura}: " . number_format($saldo, 0, ',', '.') . " Gs\n";
        }
    }
    
    echo "\n✅ NO HAY ERRORES - El problema puede ser con la apertura de caja\n\n";
    
    // Verificar apertura
    echo "3. Verificando apertura de caja del usuario logueado...\n";
    $user = \App\Models\User::first();
    
    if (!$user) {
        echo "   ✗ No hay usuarios en el sistema\n";
    } elseif (!$user->empleado) {
        echo "   ✗ Usuario no tiene empleado asociado\n";
        echo "   Usuario ID: {$user->id} - Email: {$user->email}\n";
    } else {
        echo "   Usuario: {$user->name}\n";
        echo "   Empleado ID: {$user->empleado->cod_empleado}\n";
        
        $apertura = \App\Models\AperturaCaja::where('cod_cajero', $user->empleado->cod_empleado)
            ->where('estado', 'Abierta')
            ->first();
        
        if ($apertura) {
            echo "   ✓ Apertura encontrada: {$apertura->cod_apertura}\n";
        } else {
            echo "   ✗ NO HAY APERTURA ABIERTA - Este es el problema!\n";
            echo "   El usuario debe abrir caja antes de poder cobrar\n";
        }
    }

} catch (\Exception $e) {
    echo "\n❌ ERROR ENCONTRADO:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
