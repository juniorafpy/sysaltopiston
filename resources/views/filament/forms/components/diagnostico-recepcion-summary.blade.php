@php
    $recepcionId = $get('recepcion_vehiculo_id');
    $recepcion = \App\Filament\Resources\DiagnosticoResource::resolveRecepcion($recepcionId);
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
    <div class="col-span-1 p-4 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Chapa / Vehículo</h3>
        <p class="mt-1 text-lg font-semibold text-primary-600 dark:text-primary-500">
            {{ $recepcion?->vehiculo?->matricula ?? '—' }}
        </p>
    </div>

    <div class="col-span-1 p-4 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Marca</h3>
        <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
            {{ $recepcion?->vehiculo?->marca?->descripcion ?? '—' }}
        </p>
    </div>

    <div class="col-span-1 p-4 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Modelo</h3>
        <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
            {{ $recepcion?->vehiculo?->modelo?->descripcion ?? '—' }}
        </p>
    </div>

    <div class="col-span-1 p-4 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Cliente</h3>
        <p class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">
            {{ $recepcion?->cliente?->nombres ?? '—' }}
        </p>
    </div>

    <div class="col-span-1 md:col-span-2 p-4 rounded-lg border border-blue-300 dark:border-blue-700 bg-blue-50 dark:bg-blue-800/20">
        <h3 class="text-sm font-medium text-blue-500 dark:text-blue-400">Motivo del ingreso</h3>
        <p class="mt-1 text-lg font-semibold text-blue-900 dark:text-blue-200">
            {{ $recepcion?->motivo_ingreso ?? '—' }}
        </p>
    </div>
</div>
