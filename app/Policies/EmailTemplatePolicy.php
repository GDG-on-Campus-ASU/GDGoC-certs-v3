<?php

namespace App\Policies;

use App\Models\EmailTemplate;
use App\Models\User;

class EmailTemplatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, EmailTemplate $emailTemplate): bool
    {
        return $emailTemplate->is_global || $user->id === $emailTemplate->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, EmailTemplate $emailTemplate): bool
    {
        return $user->id === $emailTemplate->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, EmailTemplate $emailTemplate): bool
    {
        return $user->id === $emailTemplate->user_id;
    }

    /**
     * Determine whether the user can clone the model.
     */
    public function clone(User $user, EmailTemplate $emailTemplate): bool
    {
        return $emailTemplate->is_global || $user->id === $emailTemplate->user_id;
    }

    /**
     * Determine whether the user can reset the model.
     */
    public function reset(User $user, EmailTemplate $emailTemplate): bool
    {
        return $user->id === $emailTemplate->user_id;
    }
}
