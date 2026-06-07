<x-filament-panels::page>
    {{ $this->form }}

    <!-- Script para SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Evento para error de stock
            Livewire.on('show-stock-error', (event) => {
                Swal.fire({
                    icon: 'error',
                    title: '⛔ Stock Insuficiente',
                    text: event.message,
                    confirmButtonText: 'Entendido',
                    confirmButtonColor: '#d33'
                });
            });

            // Evento para éxito y redirección
            Livewire.on('orden-finalizada', () => {
                Swal.fire({
                    icon: 'success',
                    title: '¡Orden Finalizada!',
                    text: 'La orden ha sido cerrada correctamente.',
                    confirmButtonText: 'Ver Lista',
                    confirmButtonColor: '#10b981'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = '{{ \App\Filament\Resources\OrdenServicioResource::getUrl("index") }}';
                    }
                });
            });
        });
    </script>
</x-filament-panels::page>
