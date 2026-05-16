<?php

namespace App\Filament\Pages;

use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;

class Login extends BaseLogin
{
    public function authenticate(): ?LoginResponse
    {
        $credentials = $this->form->getState();
        $email = $credentials['email'];
        $password = $credentials['password'];

        $user = \App\Models\User::where('email', $email)->first();

        if ($user && $user->bloqueado === 'S') {
            throw ValidationException::withMessages([
                'data.email' => 'Su cuenta se encuentra bloqueada',
            ]);
        }

        if (!Auth::attempt(['email' => $email, 'password' => $password])) {
            if ($user) {
                $user->increment('intentos_fallidos');
                if ($user->intentos_fallidos >= 3) {
                    $user->update(['bloqueado' => 'S']);
                    throw ValidationException::withMessages([
                        'data.email' => 'Su cuenta se encuentra bloqueada',
                    ]);
                }
                $remaining = 3 - $user->intentos_fallidos;
                throw ValidationException::withMessages([
                    'data.email' => "Credenciales incorrectas. Intentos restantes: {$remaining}",
                ]);
            }
            throw ValidationException::withMessages([
                'data.email' => 'Credenciales incorrectas',
            ]);
        }

        // Reset attempts on successful login
        if ($user) {
            $user->update(['intentos_fallidos' => 0]);
        }

        return app(LoginResponse::class);
    }
}
