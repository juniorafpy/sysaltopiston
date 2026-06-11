<x-filament-panels::page.simple>
    <!-- Fondo particles.js -->
    <div id="particles-js" style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:-1;"></div>

    <x-filament-panels::form wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    <script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            particlesJS('particles-js', {
                particles: {
                    number: { value: 80, density: { enable: true, value_area: 800 } },
                    color: { value: '#3b82f6' },
                    shape: { type: 'circle' },
                    opacity: { value: 0.5, random: false },
                    size: { value: 3, random: true },
                    line_linked: { enable: true, distance: 150, color: '#3b82f6', opacity: 0.4, width: 1 },
                    move: { enable: true, speed: 2, direction: 'none', random: false, straight: false, out_mode: 'out', bounce: false }
                },
                interactivity: {
                    detect_on: 'canvas',
                    events: { onhover: { enable: true, mode: 'grab' }, onclick: { enable: true, mode: 'push' }, resize: true },
                    modes: { grab: { distance: 140, line_linked: { opacity: 1 } }, push: { particles_nb: 4 } }
                },
                retina_detect: true
            });
        });
    </script>

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
</x-filament-panels::page.simple>
