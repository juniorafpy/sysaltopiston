<x-filament-panels::page>
    {{ $this->form }}

    @if($this->activeTab === 'pedidos' && $this->pedidos_resultados)
        <div class="mt-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Resultados: {{ $this->pedidos_resultados->count() }} pedidos encontrados
                </h3>
                @if($this->pedidos_resultados->count() > 0)
                <div class="flex gap-2">
                    <a href="{{ route('reportes.compras.pdf', ['tab' => 'pedidos', 'fecha_desde' => $this->pedido_fecha_desde, 'fecha_hasta' => $this->pedido_fecha_hasta, 'estado' => $this->pedido_estado]) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-danger-600 px-4 py-2 text-sm font-medium text-white hover:bg-danger-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        PDF
                    </a>
                    <a href="{{ route('reportes.compras.excel', ['tab' => 'pedidos', 'fecha_desde' => $this->pedido_fecha_desde, 'fecha_hasta' => $this->pedido_fecha_hasta, 'estado' => $this->pedido_estado]) }}" class="inline-flex items-center gap-2 rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white hover:bg-success-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Excel
                    </a>
                </div>
                @endif
            </div>
            
            @if($this->pedidos_resultados->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Código</th>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Empleado</th>
                                <th class="px-4 py-3">Sucursal</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Items</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->pedidos_resultados as $pedido)
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">#{{ $pedido->cod_pedido }}</td>
                                    <td class="px-4 py-3">{{ $pedido->fec_pedido ? $pedido->fec_pedido->format('d/m/Y') : '—' }}</td>
                                    <td class="px-4 py-3">{{ $pedido->ped_empleados?->persona?->nombre_completo ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $pedido->sucursal_ped?->descripcion ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        @switch($pedido->estado)
                                            @case('PENDIENTE')
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ $pedido->estado }}</span>
                                                @break
                                            @case('APROBADO')
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $pedido->estado }}</span>
                                                @break
                                            @case('RECHAZADO')
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ $pedido->estado }}</span>
                                                @break
                                            @case('CANCELADO')
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $pedido->estado }}</span>
                                                @break
                                            @default
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $pedido->estado }}</span>
                                        @endswitch
                                    </td>
                                    <td class="px-4 py-3">{{ $pedido->detalles->count() }} items</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-4 text-center text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    No se encontraron pedidos con los filtros aplicados.
                </div>
            @endif
        </div>
    @endif

    @if($this->activeTab === 'ordenes' && $this->ordenes_resultados)
        <div class="mt-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Resultados: {{ $this->ordenes_resultados->count() }} órdenes encontradas
                </h3>
                @if($this->ordenes_resultados->count() > 0)
                <div class="flex gap-2">
                    <a href="{{ route('reportes.compras.pdf', ['tab' => 'ordenes', 'fecha_desde' => $this->orden_fecha_desde, 'fecha_hasta' => $this->orden_fecha_hasta, 'proveedor' => $this->orden_proveedor, 'estado' => $this->orden_estado]) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-danger-600 px-4 py-2 text-sm font-medium text-white hover:bg-danger-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        PDF
                    </a>
                    <a href="{{ route('reportes.compras.excel', ['tab' => 'ordenes', 'fecha_desde' => $this->orden_fecha_desde, 'fecha_hasta' => $this->orden_fecha_hasta, 'proveedor' => $this->orden_proveedor, 'estado' => $this->orden_estado]) }}" class="inline-flex items-center gap-2 rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white hover:bg-success-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Excel
                    </a>
                </div>
                @endif
            </div>
            
            @if($this->ordenes_resultados->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">N° Orden</th>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Proveedor</th>
                                <th class="px-4 py-3">Sucursal</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->ordenes_resultados as $orden)
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">#{{ $orden->nro_orden_compra }}</td>
                                    <td class="px-4 py-3">{{ $orden->fec_orden ? $orden->fec_orden->format('d/m/Y') : '—' }}</td>
                                    <td class="px-4 py-3">{{ $orden->proveedor?->personas_pro?->nombre_completo ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $orden->sucursale?->descripcion ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        @switch($orden->estado)
                                            @case('PENDIENTE')
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">{{ $orden->estado }}</span>
                                                @break
                                            @case('APROBADO')
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $orden->estado }}</span>
                                                @break
                                            @case('RECIBIDO')
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">{{ $orden->estado }}</span>
                                                @break
                                            @case('CANCELADO')
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ $orden->estado }}</span>
                                                @break
                                            @default
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $orden->estado }}</span>
                                        @endswitch
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium">
                                        {{ number_format($orden->monto_general ?? 0, 0, ',', '.') }} Gs
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-4 text-center text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    No se encontraron órdenes con los filtros aplicados.
                </div>
            @endif
        </div>
    @endif

    @if($this->activeTab === 'facturas' && $this->facturas_resultados)
        <div class="mt-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                    Resultados: {{ $this->facturas_resultados->count() }} facturas encontradas
                </h3>
                @if($this->facturas_resultados->count() > 0)
                <div class="flex gap-2">
                    <a href="{{ route('reportes.compras.pdf', ['tab' => 'facturas', 'fecha_desde' => $this->factura_fecha_desde, 'fecha_hasta' => $this->factura_fecha_hasta, 'numero' => $this->factura_numero, 'tipo' => $this->factura_tipo]) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-danger-600 px-4 py-2 text-sm font-medium text-white hover:bg-danger-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                        PDF
                    </a>
                    <a href="{{ route('reportes.compras.excel', ['tab' => 'facturas', 'fecha_desde' => $this->factura_fecha_desde, 'fecha_hasta' => $this->factura_fecha_hasta, 'numero' => $this->factura_numero, 'tipo' => $this->factura_tipo]) }}" class="inline-flex items-center gap-2 rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white hover:bg-success-500">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        Excel
                    </a>
                </div>
                @endif
            </div>
            
            @if($this->facturas_resultados->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th class="px-4 py-3">Comprobante</th>
                                <th class="px-4 py-3">Fecha</th>
                                <th class="px-4 py-3">Proveedor</th>
                                <th class="px-4 py-3">Sucursal</th>
                                <th class="px-4 py-3">Tipo</th>
                                <th class="px-4 py-3 text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->facturas_resultados as $factura)
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                        {{ $factura->tip_comprobante }}-{{ $factura->ser_comprobante }}-{{ $factura->nro_comprobante }}
                                    </td>
                                    <td class="px-4 py-3">{{ $factura->fec_comprobante ? $factura->fec_comprobante->format('d/m/Y') : '—' }}</td>
                                    <td class="px-4 py-3">{{ $factura->proveedor?->personas_pro?->nombre_completo ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $factura->sucursal?->descripcion ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        @switch($factura->tip_comprobante)
                                            @case('FAC')
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">{{ $factura->tip_comprobante }}</span>
                                                @break
                                            @case('NCR')
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">{{ $factura->tip_comprobante }}</span>
                                                @break
                                            @case('NDB')
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">{{ $factura->tip_comprobante }}</span>
                                                @break
                                            @default
                                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">{{ $factura->tip_comprobante }}</span>
                                        @endswitch
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium">
                                        {{ number_format($factura->monto_general ?? 0, 0, ',', '.') }} Gs
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-4 text-center text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    No se encontraron facturas con los filtros aplicados.
                </div>
            @endif
        </div>
    @endif
</x-filament-panels::page>
