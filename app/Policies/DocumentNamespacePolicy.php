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
     * Determine whether the user can view the model.
     */
    public function view(User $user, DocumentNamespace $documentNamespace): bool
    {
        return $this->isOwner($user, $documentNamespace);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    private function isOwner(User $user, DocumentNamespace $documentNamespace): bool
    {
        return $user->getKey() === $documentNamespace->owner_user_id;
    }
}
