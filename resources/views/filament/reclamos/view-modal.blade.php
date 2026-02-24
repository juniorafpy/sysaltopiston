<div class="space-y-4 text-sm">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <div class="text-gray-500">Cliente</div>
            <div class="font-medium text-gray-900 dark:text-gray-100">
                {{ $record->cliente?->razon_social ?? trim(($record->cliente?->nombres ?? '') . ' ' . ($record->cliente?->apellidos ?? '')) ?: 'N/A' }}
            </div>
        </div>

        <div>
            <div class="text-gray-500">OS Referencia</div>
            <div class="font-medium text-gray-900 dark:text-gray-100">
                {{ $record->orden_servicio_id ? 'OS #' . $record->orden_servicio_id : 'N/A' }}
            </div>
        </div>

        <div>
            <div class="text-gray-500">Vehículo / Chapa</div>
            <div class="font-medium text-gray-900 dark:text-gray-100">
                {{ $record->matricula ?? 'N/A' }}
            </div>
        </div>

        <div>
            <div class="text-gray-500">Tipo de Reclamo</div>
            <div class="font-medium text-gray-900 dark:text-gray-100">
                {{ $record->tipoReclamo?->descripcion ?? 'N/A' }}
            </div>
        </div>

        <div>
            <div class="text-gray-500">Fecha del Reclamo</div>
            <div class="font-medium text-gray-900 dark:text-gray-100">
                {{ $record->fecha_reclamo?->format('d/m/Y') ?? 'N/A' }}
            </div>
        </div>

        <div>
            <div class="text-gray-500">Prioridad</div>
            <div class="font-medium text-gray-900 dark:text-gray-100">
                {{ $record->prioridad ?? 'N/A' }}
            </div>
        </div>

        <div>
            <div class="text-gray-500">Usuario Registro</div>
            <div class="font-medium text-gray-900 dark:text-gray-100">
                {{ $record->usuarioAlta?->name ?? 'N/A' }}
            </div>
        </div>

        <div>
            <div class="text-gray-500">Fecha Registro</div>
            <div class="font-medium text-gray-900 dark:text-gray-100">
                {{ $record->fecha_alta?->format('d/m/Y H:i') ?? 'N/A' }}
            </div>
        </div>
    </div>

    <div>
        <div class="text-gray-500">Descripción</div>
        <div class="mt-1 rounded-lg border border-gray-200 dark:border-gray-700 p-3 text-gray-900 dark:text-gray-100 whitespace-pre-wrap">
            {{ $record->descripcion ?? 'Sin descripción' }}
        </div>
    </div>
</div>
