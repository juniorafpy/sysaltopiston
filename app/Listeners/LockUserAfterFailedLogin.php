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
            // Verificar si el name es válido (case-insensitive)
            $inputName = request()->name ?? request()->data['name'] ?? null;
            $user = User::whereRaw('LOWER(name) = ?', [strtolower($inputName)])->first();

            if (!$user) {
                Log::warning("Intento fallido con name no registrado: " . $inputName);
                return;
            }

            if ($user->bloqueado === 'S') {
                Log::warning("El usuario {$user->name} ya está bloqueado.");
                return;
            }

            // Manejo de intentos fallidos
            $limiter = app(RateLimiter::class);
            $key = 'login_attempts:' . $user->name;
            $maxAttempts = 3; // Número de intentos antes de bloquear

            $limiter->hit($key, 60); // Registrar intento fallido

            Log::info("Intento fallido para {$user->name}. Intentos: " . $limiter->attempts($key));

            // Si supera los intentos, bloquear al usuario
            if ($limiter->attempts($key) >= $maxAttempts) {
                $user->update(['bloqueado' => 'S']);
                Log::info("Usuario {$user->name} bloqueado.");
            }
        }

}
