<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use Illuminate\Auth\Events\Failed;
use Illuminate\Cache\RateLimiter;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class LockUserAfterFailedLogin
{
        public function handle(Failed $event)
        {
            // Verificar si el email es válido
            $user = User::where('email', request()->email)->first();

            if (!$user) {
                Log::warning("Intento fallido con email no registrado: " . request()->email);
                return;
            }

            if ($user->is_locked) {
                Log::warning("El usuario {$user->email} ya está bloqueado.");
                return;
            }

            // Manejo de intentos fallidos
            $limiter = app(RateLimiter::class);
            $key = 'login_attempts:' . $user->email;
            $maxAttempts = 3; // Número de intentos antes de bloquear

            $limiter->hit($key, 60); // Registrar intento fallido

            Log::info("Intento fallido para {$user->email}. Intentos: " . $limiter->attempts($key));

            // Si supera los intentos, bloquear al usuario
            if ($limiter->attempts($key) >= $maxAttempts) {
                $user->update(['is_locked' => true]);
                Log::info("Usuario {$user->email} bloqueado.");
            }
        }

}
