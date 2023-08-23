<?php

namespace App\Policies;

use App\Models\SubjectLoad;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SubjectLoadPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Superadmin', 'Principal', 'Subject Teacher']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SubjectLoad $subjectLoad): bool
    {
        return $user->hasRole(['Superadmin', 'Principal', 'Subject Teacher']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Superadmin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SubjectLoad $subjectLoad): bool
    {
        return $user->hasRole(['Subject Teacher']);
        // return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SubjectLoad $subjectLoad): bool
    {
        return $user->hasRole(['Superadmin']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SubjectLoad $subjectLoad): bool
    {
        return $user->hasRole(['Superadmin']);
        
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SubjectLoad $subjectLoad): bool
    {
        return $user->hasRole(['Superadmin']);
        
    }
}
