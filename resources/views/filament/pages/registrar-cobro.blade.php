<x-filament-panels::page>
    <form wire:submit="guardarCobro">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-4">
            <x-filament::button type="button" color="gray" tag="a" href="{{ route('filament.admin.pages.dashboard') }}">
                Cancelar
            </x-filament::button>
            
            <x-filament::button type="submit" color="success">
                Procesar Cobro
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
