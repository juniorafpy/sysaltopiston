<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-white/10 dark:bg-gray-900">
            <h2 class="text-lg font-semibold">Reporte de Pedidos de Compra</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                Filtre por rango de fecha y estado para generar el reporte.
            </p>

            <form method="GET" action="{{ route('informes.pedidos-compra.pdf') }}" target="_blank" class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label for="fecha_desde" class="mb-1 block text-sm font-medium">Fecha desde</label>
                    <input
                        id="fecha_desde"
                        name="fecha_desde"
                        type="date"
                        value="{{ request('fecha_desde') }}"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    >
                </div>

                <div>
                    <label for="fecha_hasta" class="mb-1 block text-sm font-medium">Fecha hasta</label>
                    <input
                        id="fecha_hasta"
                        name="fecha_hasta"
                        type="date"
                        value="{{ request('fecha_hasta') }}"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    >
                </div>

                <div>
                    <label for="estado" class="mb-1 block text-sm font-medium">Estado</label>
                    <select
                        id="estado"
                        name="estado"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-900"
                    >
                        <option value="TODOS" @selected(request('estado', 'TODOS') === 'TODOS')>Todos</option>
                        <option value="PENDIENTE" @selected(request('estado') === 'PENDIENTE')>PENDIENTE</option>
                        <option value="APROBADO" @selected(request('estado') === 'APROBADO')>APROBADO</option>
                        <option value="ANULADO" @selected(request('estado') === 'ANULADO')>ANULADO</option>
                    </select>
                </div>

                <div class="flex items-end">
                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-primary-500"
                    >
                        Generar reporte
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-filament-panels::page>
