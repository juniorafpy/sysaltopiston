<x-filament-panels::page.simple>
    <style>
        /* Fondo del body transparente para ver las particulas */
        html, body, .fi-body {
            min-height: 100vh !important;
            height: 100% !important;
            background: transparent !important;
        }
        .fi-simple-layout {
            background: transparent !important;
            position: relative;
            z-index: 1;
        }
        .fi-simple-main-ctn {
            background: transparent !important;
        }

        /* Glassmorphism SOLO en el contenedor del login */
        .fi-simple-main {
            position: relative;
            background: rgba(255, 255, 255, 0.35) !important;
            backdrop-filter: blur(20px) !important;
            -webkit-backdrop-filter: blur(20px) !important;
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37) !important;
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
            z-index: 2;
        }
        .fi-simple-main .fi-input {
            background: rgba(255, 255, 255, 0.6) !important;
            border-color: rgba(255, 255, 255, 0.3) !important;
            color: #000000 !important;
            font-size: 16px !important;
            font-weight: 500 !important;
        }
        .fi-simple-main .fi-input:focus {
            background: rgba(255, 255, 255, 0.85) !important;
            border-color: #3b82f6 !important;
            color: #000000 !important;
            font-size: 16px !important;
            font-weight: 500 !important;
        }
        .fi-simple-main .fi-input::placeholder {
            color: #475569 !important;
            font-size: 14px !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
        }
        .fi-simple-main input[type="text"],
        .fi-simple-main input[type="email"],
        .fi-simple-main input[type="password"],
        .fi-simple-main .fi-fo-text-input input {
            color: #000000 !important;
            font-size: 16px !important;
            font-weight: 500 !important;
        }
        .fi-simple-main .fi-label {
            color: #1e293b !important;
        }
    </style>

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
            // Crear el contenedor de particulas y ponerlo DIRECTAMENTE en el body
            // Asi esta FUERA del layout de Filament y cubre toda la pantalla
            var particlesDiv = document.createElement('div');
            particlesDiv.id = 'particles-js';
            particlesDiv.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;z-index:-1;pointer-events:none;';
            document.body.insertBefore(particlesDiv, document.body.firstChild);

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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Convertir a mayusculas el campo de usuario en tiempo real
            function setupUppercase() {
                var inputs = document.querySelectorAll('input[type="text"], input[type="email"], input:not([type="password"])');
                inputs.forEach(function(input) {
                    if (input.type === 'password') return;
                    
                    input.addEventListener('input', function() {
                        this.value = this.value.toUpperCase();
                    });
                    
                    input.addEventListener('paste', function(e) {
                        var self = this;
                        setTimeout(function() {
                            self.value = self.value.toUpperCase();
                        }, 0);
                    });
                });
            }
            
            setTimeout(setupUppercase, 300);
            setTimeout(setupUppercase, 800);
            setTimeout(setupUppercase, 1500);
        });
    </script>
</x-filament-panels::page.simple>
