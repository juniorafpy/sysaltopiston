<x-filament-panels::page>
    <div class="space-y-4">
        {{ $this->form }}

        @if($this->activeTab === 'facturas' && $this->facturas_resultados && $this->facturas_resultados->count() > 0)
            <div class="flex gap-2 justify-end mb-2">
                <a href="{{ route('reportes.ventas.pdf', ['tab' => 'facturas', 'fecha_desde' => $this->factura_fecha_desde, 'fecha_hasta' => $this->factura_fecha_hasta, 'cliente' => $this->factura_cliente, 'estado' => $this->factura_estado, 'condicion' => $this->factura_condicion]) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-danger-600 px-4 py-2 text-sm font-medium text-white hover:bg-danger-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m-6.102-3.809L12.75 12.75m-6.275 2.086a43.953 43.953 0 011.158 6.562m-1.158-6.562L5.25 6.75M17.25 18l.753.753a43.953 43.953 0 001.158-6.562m-1.158 6.562L18.75 6.75" /></svg>
                    PDF
                </a>
                <a href="{{ route('reportes.ventas.excel', ['tab' => 'facturas', 'fecha_desde' => $this->factura_fecha_desde, 'fecha_hasta' => $this->factura_fecha_hasta, 'cliente' => $this->factura_cliente, 'estado' => $this->factura_estado, 'condicion' => $this->factura_condicion]) }}" class="inline-flex items-center gap-2 rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white hover:bg-success-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    Excel
                </a>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 font-semibold">N°</th>
                                <th class="px-4 py-3 font-semibold">Fecha</th>
                                <th class="px-4 py-3 font-semibold">Cliente</th>
                                <th class="px-4 py-3 font-semibold">Condición</th>
                                <th class="px-4 py-3 font-semibold">Estado</th>
                                <th class="px-4 py-3 font-semibold text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->facturas_resultados as $factura)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3 font-medium">{{ $factura->numero_factura ?? $factura->cod_factura }}</td>
                                    <td class="px-4 py-3">{{ $factura->fecha_factura ? $factura->fecha_factura->format('d/m/Y') : '-' }}</td>
                                    <td class="px-4 py-3">{{ $factura->cliente?->nombre_completo ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $factura->condicion_venta ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        @switch($factura->estado)
                                            @case('Emitida')
                                                <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20">Emitida</span>
                                                @break
                                            @case('Anulada')
                                                <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20">Anulada</span>
                                                @break
                                            @case('Pendiente')
                                                <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/20">Pendiente</span>
                                                @break
                                            @default
                                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">{{ $factura->estado }}</span>
                                        @endswitch
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold">{{ number_format($factura->total_general ?? 0, 0, ',', '.') }} Gs.</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total: {{ $this->facturas_resultados->count() }} registros</p>
                </div>
            </div>
        @endif

        @if($this->activeTab === 'cobros' && $this->cobros_resultados && $this->cobros_resultados->count() > 0)
            <div class="flex gap-2 justify-end mb-2">
                <a href="{{ route('reportes.ventas.pdf', ['tab' => 'cobros', 'fecha_desde' => $this->cobro_fecha_desde, 'fecha_hasta' => $this->cobro_fecha_hasta, 'cliente' => $this->cobro_cliente, 'estado' => $this->cobro_estado]) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-danger-600 px-4 py-2 text-sm font-medium text-white hover:bg-danger-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m-6.102-3.809L12.75 12.75m-6.275 2.086a43.953 43.953 0 011.158 6.562m-1.158-6.562L5.25 6.75M17.25 18l.753.753a43.953 43.953 0 001.158-6.562m-1.158 6.562L18.75 6.75" /></svg>
                    PDF
                </a>
                <a href="{{ route('reportes.ventas.excel', ['tab' => 'cobros', 'fecha_desde' => $this->cobro_fecha_desde, 'fecha_hasta' => $this->cobro_fecha_hasta, 'cliente' => $this->cobro_cliente, 'estado' => $this->cobro_estado]) }}" class="inline-flex items-center gap-2 rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white hover:bg-success-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    Excel
                </a>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 font-semibold">N°</th>
                                <th class="px-4 py-3 font-semibold">Fecha</th>
                                <th class="px-4 py-3 font-semibold">Cliente</th>
                                <th class="px-4 py-3 font-semibold">Estado</th>
                                <th class="px-4 py-3 font-semibold text-right">Monto</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->cobros_resultados as $cobro)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3 font-medium">{{ $cobro->cod_cobro }}</td>
                                    <td class="px-4 py-3">{{ $cobro->fecha_cobro ? $cobro->fecha_cobro->format('d/m/Y') : '-' }}</td>
                                    <td class="px-4 py-3">{{ $cobro->cliente?->nombre_completo ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        @switch($cobro->estado)
                                            @case('Completado')
                                                <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20">Completado</span>
                                                @break
                                            @case('Pendiente')
                                                <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/20">Pendiente</span>
                                                @break
                                            @case('Anulado')
                                                <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/20">Anulado</span>
                                                @break
                                            @default
                                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">{{ $cobro->estado }}</span>
                                        @endswitch
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold">{{ number_format($cobro->monto_total ?? 0, 0, ',', '.') }} Gs.</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total: {{ $this->cobros_resultados->count() }} registros</p>
                </div>
            </div>
        @endif

        @if($this->activeTab === 'aperturas' && $this->aperturas_resultados && $this->aperturas_resultados->count() > 0)
            <div class="flex gap-2 justify-end mb-2">
                <a href="{{ route('reportes.ventas.pdf', ['tab' => 'aperturas', 'fecha_desde' => $this->apertura_fecha_desde, 'fecha_hasta' => $this->apertura_fecha_hasta, 'estado' => $this->apertura_estado]) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-danger-600 px-4 py-2 text-sm font-medium text-white hover:bg-danger-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m-6.102-3.809L12.75 12.75m-6.275 2.086a43.953 43.953 0 011.158 6.562m-1.158-6.562L5.25 6.75M17.25 18l.753.753a43.953 43.953 0 001.158-6.562m-1.158 6.562L18.75 6.75" /></svg>
                    PDF
                </a>
                <a href="{{ route('reportes.ventas.excel', ['tab' => 'aperturas', 'fecha_desde' => $this->apertura_fecha_desde, 'fecha_hasta' => $this->apertura_fecha_hasta, 'estado' => $this->apertura_estado]) }}" class="inline-flex items-center gap-2 rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white hover:bg-success-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                    Excel
                </a>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="px-4 py-3 font-semibold">N°</th>
                                <th class="px-4 py-3 font-semibold">Fecha Apertura</th>
                                <th class="px-4 py-3 font-semibold">Caja</th>
                                <th class="px-4 py-3 font-semibold">Usuario</th>
                                <th class="px-4 py-3 font-semibold">Estado</th>
                                <th class="px-4 py-3 font-semibold text-right">Monto Inicial</th>
                                <th class="px-4 py-3 font-semibold text-right">Saldo Esperado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->aperturas_resultados as $apertura)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3 font-medium">{{ $apertura->cod_apertura }}</td>
                                    <td class="px-4 py-3">{{ $apertura->fecha_apertura ? $apertura->fecha_apertura->format('d/m/Y') : '-' }}</td>
                                    <td class="px-4 py-3">{{ $apertura->caja?->descripcion ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $apertura->usuario ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        @if($apertura->estado === 'Abierta')
                                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20">Abierta</span>
                                        @else
                                            <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">Cerrada</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold">{{ number_format($apertura->monto_inicial ?? 0, 0, ',', '.') }} Gs.</td>
                                    <td class="px-4 py-3 text-right font-semibold">{{ number_format($apertura->saldo_esperado ?? 0, 0, ',', '.') }} Gs.</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total: {{ $this->aperturas_resultados->count() }} registros</p>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
