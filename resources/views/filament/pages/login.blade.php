<x-filament-panels::page.simple>
    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>
</x-filament-panels::page.simple>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('swal:blocked', (data) => {
            Swal.fire({
                icon: 'error',
                title: 'Cuenta Bloqueada',
                text: data.message || 'Su cuenta se encuentra bloqueada. Comuníquese con el administrador.',
                confirmButtonText: 'Aceptar',
                confirmButtonColor: '#dc2626'
            });
        });
    });
</script>
