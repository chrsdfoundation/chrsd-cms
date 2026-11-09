<?php

namespace App\Policies;

use App\Models\BusinessCard;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BusinessCardPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_business_card');
    }

    public function view(User $user, BusinessCard $businessCard): bool
    {
        return $user->can('view_business_card');
    }

    public function create(User $user): bool
    {
        return $user->can('create_business_card');
    }

    public function update(User $user, BusinessCard $businessCard): bool
    {
        return $user->can('update_business_card');
    }

    public function delete(User $user, BusinessCard $businessCard): bool
    {
        return $user->can('delete_business_card');
    }

    public function deleteAny(User $user): bool
    {
        return $user->can('delete_any_business_card');
    }

    public function forceDelete(User $user, BusinessCard $businessCard): bool
    {
        return $user->can('force_delete_business_card');
    }

    public function forceDeleteAny(User $user): bool
    {
        return $user->can('force_delete_any_business_card');
    }

    public function restore(User $user, BusinessCard $businessCard): bool
    {
        return $user->can('restore_business_card');
    }

    public function restoreAny(User $user): bool
    {
        return $user->can('restore_any_business_card');
    }

    public function replicate(User $user, BusinessCard $businessCard): bool
    {
        return $user->can('replicate_business_card');
    }

    public function reorder(User $user): bool
    {
        return $user->can('reorder_business_card');
    }

    /** Approve the card for press, and mark it sent. Separate from editing. */
    public function approve(User $user, BusinessCard $businessCard): bool
    {
        return $user->can('approve_business_card');
    }
}
