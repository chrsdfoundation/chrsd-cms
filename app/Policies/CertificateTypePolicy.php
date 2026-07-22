<?php

namespace App\Policies;

use App\Models\CertificateType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CertificateTypePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_any_certificate::type');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CertificateType $certificateType): bool
    {
        return $user->can('view_certificate::type');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create_certificate::type');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CertificateType $certificateType): bool
    {
        return $user->can('update_certificate::type');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CertificateType $certificateType): bool
    {
        return $user->can('delete_certificate::type');
    }

    /**
     * Determine whether the user can bulk delete.
     */
    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_certificate::type');
    }

    /**
     * Determine whether the user can permanently delete.
     */
    public function forceDelete(User $user, CertificateType $certificateType): bool
    {
        return $user->can('force_delete_certificate::type');
    }

    /**
     * Determine whether the user can permanently bulk delete.
     */
    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_certificate::type');
    }

    /**
     * Determine whether the user can restore.
     */
    public function restore(User $user, CertificateType $certificateType): bool
    {
        return $user->can('restore_certificate::type');
    }

    /**
     * Determine whether the user can bulk restore.
     */
    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_certificate::type');
    }

    /**
     * Determine whether the user can replicate.
     */
    public function replicate(User $user, CertificateType $certificateType): bool
    {
        return $user->can('replicate_certificate::type');
    }

    /**
     * Determine whether the user can reorder.
     */
    public function reorder(User $user): bool
    {
        return $user->can('reorder_certificate::type');
    }
}
