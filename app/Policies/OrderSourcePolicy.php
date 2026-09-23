<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\OrderSource;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderSourcePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:OrderSource');
    }

    public function view(AuthUser $authUser, OrderSource $orderSource): bool
    {
        return $authUser->can('View:OrderSource');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:OrderSource');
    }

    public function update(AuthUser $authUser, OrderSource $orderSource): bool
    {
        return $authUser->can('Update:OrderSource');
    }

    public function delete(AuthUser $authUser, OrderSource $orderSource): bool
    {
        return $authUser->can('Delete:OrderSource');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:OrderSource');
    }

    public function restore(AuthUser $authUser, OrderSource $orderSource): bool
    {
        return $authUser->can('Restore:OrderSource');
    }

    public function forceDelete(AuthUser $authUser, OrderSource $orderSource): bool
    {
        return $authUser->can('ForceDelete:OrderSource');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:OrderSource');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:OrderSource');
    }

    public function replicate(AuthUser $authUser, OrderSource $orderSource): bool
    {
        return $authUser->can('Replicate:OrderSource');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:OrderSource');
    }

}