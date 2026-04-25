<?php

namespace App\Policies;

use App\Models\Color;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ColorPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_color');
    }

    public function view(User $user, Color $color): bool
    {
        return $user->can('view_color');
    }

    public function create(User $user): bool
    {
        return $user->can('create_color');
    }

    public function update(User $user, Color $color): bool
    {
        return $user->can('update_color');
    }

    public function delete(User $user, Color $color): bool
    {
        return $user->can('delete_color');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_color');
    }

    public function forceDelete(User $user, Color $color): bool
    {
        return $user->can('force_delete_color');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_color');
    }

    public function restore(User $user, Color $color): bool
    {
        return $user->can('restore_color');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_color');
    }

    public function replicate(User $user, Color $color): bool
    {
        return $user->can('replicate_color');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_color');
    }
}
