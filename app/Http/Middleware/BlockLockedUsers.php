<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Models\User;
use Filament\Notifications\Notification;

class BlockLockedUsers
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::where('email', $request->email)->first();

        if ($user && $user->is_locked) {
            Notification::make()
                ->title('Tu cuenta ha sido bloqueada. Contacta con el administrador.')
                ->danger()
                ->send();

            return back()->withErrors(['email' => 'Tu cuenta está bloqueada.']);
        }

        return $next($request);
    }
}
