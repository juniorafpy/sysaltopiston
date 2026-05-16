<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable {

    use HasApiTokens, HasFactory, Notifiable, HasRoles;

      public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'cod_sucursal',
        'cod_empleado',
        'cod_persona',
        'usuario_alta',
        'fec_alta',
        'intentos_fallidos',
        'bloqueado',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Relación con la sucursal del usuario
     */
    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'cod_sucursal', 'cod_sucursal');
    }

    /**
     * Relación con el empleado del usuario
     */
    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleados::class, 'cod_empleado', 'cod_empleado');
    }

    /**
     * Relación con la persona del usuario
     */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Personas::class, 'cod_persona', 'cod_persona');
    }

    /**
     * Relación con el mecánico del usuario (si tiene)
     */
    public function mecanico(): BelongsTo
    {
        return $this->belongsTo(Mecanico::class, 'cod_empleado', 'cod_empleado');
    }

}
