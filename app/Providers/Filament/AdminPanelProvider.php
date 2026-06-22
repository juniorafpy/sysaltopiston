<?php

namespace App\Providers\Filament;

use Filament\Pages;
use Filament\Panel;
use Filament\Widgets;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Http\Middleware\Authenticate;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Filament\Http\Middleware\AuthenticateSession;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel

            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Pages\Login::class)
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])


            ->resources([
                \App\Filament\Resources\RoleResource::class,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
            ])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
             //   Widgets\FilamentInfoWidget::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->collapsibleNavigationGroups()
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Principal')
                    ->icon('heroicon-o-home')
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('Referenciales')
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('Referenciales/Servicios')
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('Referenciales/Ventas')
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('Gestión de Compra')
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('Gestión Servicios')
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('Gestión Ventas')
                    ->collapsed(true),
            ])
            ->renderHook('panels::head.start', fn() => '<link rel="stylesheet" href="'.asset('css/filament-admin.css').'">')
            ->renderHook('panels::body.end', function () {
                $swal = '';
                if (session()->has('swal-caja-cerrada')) {
                    $msg = addslashes(session()->pull('swal-caja-cerrada'));
                    $swal = 'Swal.fire({icon:"error",title:"Caja Cerrada",text:"' . $msg . '",confirmButtonText:"Aceptar",confirmButtonColor:"#dc2626"});';
                }

                return '
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script>
                    document.addEventListener("livewire:initialized", () => {
                        ' . $swal . '
                        const Toast = Swal.mixin({
                            toast: true,
                            position: "top-end",
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                        });

                        Livewire.on("swal:error", (data) => {
                            Swal.fire({
                                icon: "error",
                                title: "Error",
                                text: data.message,
                                width: "350px",
                                confirmButtonText: "Aceptar",
                                confirmButtonColor: "#dc2626"
                            });
                        });

                        Livewire.on("swal:success", (data) => {
                            Toast.fire({
                                icon: "success",
                                title: data.message
                            });
                        });

                        Livewire.on("swal:success-modal", (data) => {
                            Swal.fire({
                                icon: "success",
                                title: data.title || "Éxito",
                                text: data.message,
                                confirmButtonText: "Aceptar",
                                confirmButtonColor: "#16a34a",
                                allowOutsideClick: false
                            });
                        });

                        Livewire.on("documento-duplicado", (data) => {
                            Swal.fire({
                                icon: "warning",
                                title: "Documento ya registrado",
                                html: `El número de documento <b>${data.documento}</b> ya está registrado en la persona con <b>ID: ${data.id}</b>`,
                                confirmButtonText: "Aceptar",
                                confirmButtonColor: "#f59e0b"
                            });
                        });

                        // Interceptar errores de validación de Livewire
                        Livewire.hook("request", ({ uri, options, payload, respond, succeed, fail }) => {
                            respond((response) => {
                                if (response.effects && response.effects.errors) {
                                    const errors = Object.values(response.effects.errors).flat();
                                    if (errors.length > 0) {
                                        Swal.fire({
                                            icon: "error",
                                            title: "Error de validación",
                                            html: errors.join("<br>"),
                                            width: "400px",
                                            confirmButtonText: "Aceptar",
                                            confirmButtonColor: "#dc2626"
                                        });
                                    }
                                }
                            });
                        });

                        // Aplicar scroll al Repeater de detalles buscando el Section por título
                        const applyRepeaterScroll = () => {
                            // Buscar el Section que contiene "Facturas a Cobrar"
                            const sections = document.querySelectorAll(".fi-section");
                            sections.forEach(section => {
                                const heading = section.querySelector(".fi-section-header-heading");
                                if (heading && heading.textContent.includes("Facturas a Cobrar")) {
                                    // Buscar específicamente el contenedor de items del Repeater
                                    const repeaterItems = section.querySelector(".fi-fo-repeater-items");
                                    if (repeaterItems && !repeaterItems.dataset.scrollApplied) {
                                        repeaterItems.style.maxHeight = "400px";
                                        repeaterItems.style.overflowY = "auto";
                                        repeaterItems.style.paddingRight = "8px";
                                        repeaterItems.dataset.scrollApplied = "true";
                                    }
                                }
                            });
                        };

                        // Aplicar al cargar la página
                        applyRepeaterScroll();

                        // Verificar periódicamente cada 500ms
                        setInterval(applyRepeaterScroll, 500);
                    });
                </script>
            ';
            })
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])

            //->theme(asset('css/filament-admin.css'))

            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
