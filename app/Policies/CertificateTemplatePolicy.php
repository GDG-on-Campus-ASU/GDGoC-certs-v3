<?php

namespace App\Policies;

use App\Models\CertificateTemplate;
use App\Models\User;

class CertificateTemplatePolicy
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
    public function view(User $user, CertificateTemplate $certificateTemplate): bool
    {
        return $certificateTemplate->is_global || $user->id === $certificateTemplate->user_id;
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
    public function update(User $user, CertificateTemplate $certificateTemplate): bool
    {
        return $user->id === $certificateTemplate->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CertificateTemplate $certificateTemplate): bool
    {
        return $user->id === $certificateTemplate->user_id;
    }

    /**
     * Determine whether the user can clone the model.
     */
    public function clone(User $user, CertificateTemplate $certificateTemplate): bool
    {
        return $certificateTemplate->is_global || $user->id === $certificateTemplate->user_id;
    }

    /**
     * Determine whether the user can reset the model.
     */
    public function reset(User $user, CertificateTemplate $certificateTemplate): bool
    {
        return $user->id === $certificateTemplate->user_id;
    }
}
