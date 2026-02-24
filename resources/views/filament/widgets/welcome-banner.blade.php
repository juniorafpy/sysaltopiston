<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-1">
                <p class="text-sm text-gray-500 dark:text-gray-400">Escritorio principal</p>
                <h2 class="text-2xl font-bold tracking-tight text-gray-950 dark:text-white">{{ $saludo }}</h2>
                <p class="text-sm text-gray-600 dark:text-gray-300">{{ $fecha }}</p>
            </div>

            <div class="inline-flex items-center gap-2 rounded-xl bg-gray-100 px-4 py-2 dark:bg-gray-800">
                <x-heroicon-o-clock class="h-5 w-5 text-gray-500 dark:text-gray-300" />
                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $hora }}</span>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
