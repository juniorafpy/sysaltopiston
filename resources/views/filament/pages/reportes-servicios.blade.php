<x-filament-panels::page>
    <div class="space-y-4">
        {{ $this->form }}

        @if($this->activeTab === 'ordenes' && $this->ordenes_resultados && $this->ordenes_resultados->count() > 0)
            <div class="flex gap-2 justify-end mb-2">
                <a href="{{ route('reportes.servicios.pdf', ['tab' => 'ordenes', 'fecha_desde' => $this->orden_fecha_desde, 'fecha_hasta' => $this->orden_fecha_hasta, 'cliente' => $this->orden_cliente, 'mecanico' => $this->orden_mecanico, 'estado' => $this->orden_estado]) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-danger-600 px-4 py-2 text-sm font-medium text-white hover:bg-danger-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m-6.102-3.809L12.75 12.75m-6.275 2.086a43.953 43.953 0 011.158 6.562m-1.158-6.562L5.25 6.75M17.25 18l.753.753a43.953 43.953 0 001.158-6.562m-1.158 6.562L18.75 6.75" /></svg>
                    PDF
                </a>
                <a href="{{ route('reportes.servicios.excel', ['tab' => 'ordenes', 'fecha_desde' => $this->orden_fecha_desde, 'fecha_hasta' => $this->orden_fecha_hasta, 'cliente' => $this->orden_cliente, 'mecanico' => $this->orden_mecanico, 'estado' => $this->orden_estado]) }}" class="inline-flex items-center gap-2 rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white hover:bg-success-500">
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
                                <th class="px-4 py-3 font-semibold">Vehículo</th>
                                <th class="px-4 py-3 font-semibold">Mecánico</th>
                                <th class="px-4 py-3 font-semibold">Estado</th>
                                <th class="px-4 py-3 font-semibold text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->ordenes_resultados as $orden)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3 font-medium">{{ $orden->id }}</td>
                                    <td class="px-4 py-3">{{ $orden->fecha_inicio ? $orden->fecha_inicio->format('d/m/Y') : '-' }}</td>
                                    <td class="px-4 py-3">{{ $orden->cliente?->nombre_completo ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $orden->recepcionVehiculo?->vehiculo?->matricula ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $orden->mecanicoAsignado?->persona?->nombre_completo ?? 'Sin asignar' }}</td>
                                    <td class="px-4 py-3">
                                        @switch($orden->estado_trabajo)
                                            @case('Pendiente')
                                                <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/20">Pendiente</span>
                                                @break
                                            @case('En Proceso')
                                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/20">En Proceso</span>
                                                @break
                                            @case('Finalizado')
                                                <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20">Finalizado</span>
                                                @break
                                            @default
                                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">{{ $orden->estado_trabajo }}</span>
                                        @endswitch
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold">{{ number_format($orden->total ?? 0, 0, ',', '.') }} Gs.</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total: {{ $this->ordenes_resultados->count() }} registros</p>
                </div>
            </div>
        @endif

        @if($this->activeTab === 'recepciones' && $this->recepciones_resultados && $this->recepciones_resultados->count() > 0)
            <div class="flex gap-2 justify-end mb-2">
                <a href="{{ route('reportes.servicios.pdf', ['tab' => 'recepciones', 'fecha_desde' => $this->recepcion_fecha_desde, 'fecha_hasta' => $this->recepcion_fecha_hasta, 'cliente' => $this->recepcion_cliente, 'estado' => $this->recepcion_estado]) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-danger-600 px-4 py-2 text-sm font-medium text-white hover:bg-danger-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m-6.102-3.809L12.75 12.75m-6.275 2.086a43.953 43.953 0 011.158 6.562m-1.158-6.562L5.25 6.75M17.25 18l.753.753a43.953 43.953 0 001.158-6.562m-1.158 6.562L18.75 6.75" /></svg>
                    PDF
                </a>
                <a href="{{ route('reportes.servicios.excel', ['tab' => 'recepciones', 'fecha_desde' => $this->recepcion_fecha_desde, 'fecha_hasta' => $this->recepcion_fecha_hasta, 'cliente' => $this->recepcion_cliente, 'estado' => $this->recepcion_estado]) }}" class="inline-flex items-center gap-2 rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white hover:bg-success-500">
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
                                <th class="px-4 py-3 font-semibold">Vehículo</th>
                                <th class="px-4 py-3 font-semibold">Motivo</th>
                                <th class="px-4 py-3 font-semibold">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->recepciones_resultados as $recepcion)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3 font-medium">{{ $recepcion->id }}</td>
                                    <td class="px-4 py-3">{{ $recepcion->fecha_recepcion ? \Carbon\Carbon::parse($recepcion->fecha_recepcion)->format('d/m/Y') : '-' }}</td>
                                    <td class="px-4 py-3">{{ $recepcion->cliente?->nombre_completo ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $recepcion->vehiculo?->matricula ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $recepcion->motivo_ingreso ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">
                                        @switch($recepcion->estado)
                                            @case('Pendiente')
                                                <span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-600/20 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/20">Pendiente</span>
                                                @break
                                            @case('En Proceso')
                                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/20">En Proceso</span>
                                                @break
                                            @case('Finalizado')
                                                <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/20">Finalizado</span>
                                                @break
                                            @default
                                                <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20">{{ $recepcion->estado }}</span>
                                        @endswitch
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total: {{ $this->recepciones_resultados->count() }} registros</p>
                </div>
            </div>
        @endif

        @if($this->activeTab === 'entregas' && $this->entregas_resultados && $this->entregas_resultados->count() > 0)
            <div class="flex gap-2 justify-end mb-2">
                <a href="{{ route('reportes.servicios.pdf', ['tab' => 'entregas', 'fecha_desde' => $this->entrega_fecha_desde, 'fecha_hasta' => $this->entrega_fecha_hasta, 'cliente' => $this->entrega_cliente]) }}" target="_blank" class="inline-flex items-center gap-2 rounded-lg bg-danger-600 px-4 py-2 text-sm font-medium text-white hover:bg-danger-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m-6.102-3.809L12.75 12.75m-6.275 2.086a43.953 43.953 0 011.158 6.562m-1.158-6.562L5.25 6.75M17.25 18l.753.753a43.953 43.953 0 001.158-6.562m-1.158 6.562L18.75 6.75" /></svg>
                    PDF
                </a>
                <a href="{{ route('reportes.servicios.excel', ['tab' => 'entregas', 'fecha_desde' => $this->entrega_fecha_desde, 'fecha_hasta' => $this->entrega_fecha_hasta, 'cliente' => $this->entrega_cliente]) }}" class="inline-flex items-center gap-2 rounded-lg bg-success-600 px-4 py-2 text-sm font-medium text-white hover:bg-success-500">
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
                                <th class="px-4 py-3 font-semibold">OS</th>
                                <th class="px-4 py-3 font-semibold">Cliente</th>
                                <th class="px-4 py-3 font-semibold">Vehículo</th>
                                <th class="px-4 py-3 font-semibold">Recibe</th>
                                <th class="px-4 py-3 font-semibold">Km Salida</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($this->entregas_resultados as $entrega)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="px-4 py-3 font-medium">{{ $entrega->id }}</td>
                                    <td class="px-4 py-3">{{ $entrega->fecha_entrega ? $entrega->fecha_entrega->format('d/m/Y') : '-' }}</td>
                                    <td class="px-4 py-3">{{ $entrega->ordenServicio?->id ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $entrega->ordenServicio?->cliente?->nombre_completo ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $entrega->ordenServicio?->recepcionVehiculo?->vehiculo?->matricula ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ $entrega->persona_recibe ?? 'N/A' }}</td>
                                    <td class="px-4 py-3">{{ number_format($entrega->kilometraje_salida ?? 0, 0, ',', '.') }} km</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-gray-200 px-4 py-3 dark:border-gray-700">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total: {{ $this->entregas_resultados->count() }} registros</p>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
