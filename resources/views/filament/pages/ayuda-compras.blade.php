<x-filament-panels::page>
    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-900">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Manual de Usuario - Compras</h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                Desde aquí puede abrir o descargar el manual completo del módulo de Compras en formato PDF.
            </p>

            <div class="mt-4 flex flex-wrap gap-3">
                <a
                    href="{{ route('ayuda.compras.pdf') }}"
                    target="_blank"
                    rel="noopener"
                    class="inline-flex items-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white hover:bg-primary-500"
                >
                    Abrir manual PDF
                </a>

                <a
                    href="{{ route('ayuda.compras.pdf', ['download' => 1]) }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-800 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-100 dark:hover:bg-gray-800"
                >
                    Descargar manual PDF
                </a>
            </div>
        </div>
    </div>
</x-filament-panels::page>
