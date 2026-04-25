<?php

namespace App\Policies;

use App\Models\Mecanico;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MecanicoPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_mecanico');
    }

    public function view(User $user, Mecanico $mecanico): bool
    {
        return $user->can('view_mecanico');
    }

    public function create(User $user): bool
    {
        return $user->can('create_mecanico');
    }

    public function update(User $user, Mecanico $mecanico): bool
    {
        return $user->can('update_mecanico');
    }

    public function delete(User $user, Mecanico $mecanico): bool
    {
        return $user->can('delete_mecanico');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_mecanico');
    }

    public function forceDelete(User $user, Mecanico $mecanico): bool
    {
        return $user->can('force_delete_mecanico');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_mecanico');
    }

    public function restore(User $user, Mecanico $mecanico): bool
    {
        return $user->can('restore_mecanico');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_mecanico');
    }

    public function replicate(User $user, Mecanico $mecanico): bool
    {
        return $user->can('replicate_mecanico');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_mecanico');
    }
}
