<?php

namespace App\Filament\Widgets;

use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class WelcomeBanner extends Widget
{
    protected static string $view = 'filament.widgets.welcome-banner';

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        $user = Auth::user();
        $persona = $user?->persona;

        $nombreCompleto = trim(implode(' ', array_filter([
            $persona?->nombres,
            $persona?->apellidos,
        ])));

        if ($nombreCompleto === '') {
            $nombreCompleto = $user?->name ?? 'Usuario';
        }

        $ahora = Carbon::now()->locale('es');

        return [
            'saludo' => "Bienvenido al sistema AltoPiston {$nombreCompleto}",
            'fecha' => ucfirst($ahora->translatedFormat('l, d \d\e F \d\e Y')),
            'hora' => $ahora->format('H:i'),
        ];
    }
}
