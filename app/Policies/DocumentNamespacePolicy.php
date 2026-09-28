<?php

namespace App\Policies;

use App\Models\DocumentNamespace;
use App\Models\User;

class DocumentNamespacePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model. Guests (no
     * authenticated user) can only view public namespaces.
     */
    public function view(?User $user, DocumentNamespace $documentNamespace): bool
    {
        return ($user !== null && $this->isOwner($user, $documentNamespace))
            || $documentNamespace->is_public;
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
    public function update(User $user, DocumentNamespace $documentNamespace): bool
    {
        return $this->isOwner($user, $documentNamespace);
    }

    private function isOwner(User $user, DocumentNamespace $documentNamespace): bool
    {
        return $user->getKey() === $documentNamespace->owner_user_id;
    }
}
