<?php

namespace App\Filament\Pages;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Filament\Http\Responses\Auth\Contracts\LoginResponse;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.login';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Usuario')
                    ->required()
                    ->maxLength(6)
                    ->autocomplete(),
                TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->required()
                    ->autocomplete('current-password'),
            ])
            ->statePath('data');
    }

    public function authenticate(): ?LoginResponse
    {
        $credentials = $this->form->getState();
        $name = $credentials['name'];
        $password = $credentials['password'];

        $user = \App\Models\User::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();

        if ($user && $user->bloqueado === 'S') {
            $this->dispatch('swal:blocked', message: 'Su cuenta se encuentra bloqueada. Comuníquese con el administrador.');
            return null;
        }

        if (!$user || !Auth::attempt(['name' => $user->name, 'password' => $password])) {
            if ($user) {
                $user->increment('intentos_fallidos');
                if ($user->intentos_fallidos >= 3) {
                    $user->update(['bloqueado' => 'S']);
                    $this->dispatch('swal:blocked', message: 'Su cuenta se encuentra bloqueada. Comuníquese con el administrador.');
                    return null;
                }
                $remaining = 3 - $user->intentos_fallidos;
                throw ValidationException::withMessages([
                    'data.name' => "Credenciales incorrectas. Intentos restantes: {$remaining}",
                ]);
            }
            throw ValidationException::withMessages([
                'data.name' => 'Credenciales incorrectas',
            ]);
        }

        // Reset attempts on successful login
        if ($user) {
            $user->update(['intentos_fallidos' => 0]);
        }

        return app(LoginResponse::class);
    }
}
