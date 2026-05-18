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
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Principal')
                    ->icon('heroicon-o-home'),
                NavigationGroup::make()
                    ->label('Referenciales'),
                NavigationGroup::make()
                    ->label('Gestión Compras'),
                NavigationGroup::make()
                    ->label('Gestión Servicios'),
                NavigationGroup::make()
                    ->label('Ventas'),
                NavigationGroup::make()
                    ->label('Informes'),
                NavigationGroup::make()
                    ->label('Ayuda'),

            ])
            ->renderHook('panels::head.start', fn() => '<link rel="stylesheet" href="'.asset('css/filament-admin.css').'">')
            ->renderHook('panels::body.end', fn() => '
                <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
                <script>
                    document.addEventListener("livewire:initialized", () => {
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

                        Livewire.on("documento-duplicado", (data) => {
                            Swal.fire({
                                icon: "warning",
                                title: "Documento ya registrado",
                                html: `El número de documento <b>${data.documento}</b> ya está registrado en la persona con <b>ID: ${data.id}</b>`,
                                confirmButtonText: "Aceptar",
                                confirmButtonColor: "#f59e0b"
                            });
                        });
                    });
                </script>
            ')
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
