<?php

namespace App\Policies;

use App\Models\User;
use App\Models\GuiaRemisionCabecera;
use Illuminate\Auth\Access\HandlesAuthorization;

class GuiaRemisionCabeceraPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_guia::remision');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GuiaRemisionCabecera $guiaRemisionCabecera): bool
    {
        return $user->can('view_guia::remision');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_guia::remision');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GuiaRemisionCabecera $guiaRemisionCabecera): bool
    {
        return $user->can('update_guia::remision');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GuiaRemisionCabecera $guiaRemisionCabecera): bool
    {
        return $user->can('delete_guia::remision');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_guia::remision');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, GuiaRemisionCabecera $guiaRemisionCabecera): bool
    {
        return $user->can('force_delete_guia::remision');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_guia::remision');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, GuiaRemisionCabecera $guiaRemisionCabecera): bool
    {
        return $user->can('restore_guia::remision');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_guia::remision');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, GuiaRemisionCabecera $guiaRemisionCabecera): bool
    {
        return $user->can('replicate_guia::remision');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_guia::remision');
    }
}
