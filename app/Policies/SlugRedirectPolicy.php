<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\SlugRedirect;
use Illuminate\Auth\Access\HandlesAuthorization;

class SlugRedirectPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:SlugRedirect');
    }

    public function view(AuthUser $authUser, SlugRedirect $slugRedirect): bool
    {
        return $authUser->can('View:SlugRedirect');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:SlugRedirect');
    }

    public function update(AuthUser $authUser, SlugRedirect $slugRedirect): bool
    {
        return $authUser->can('Update:SlugRedirect');
    }

    public function delete(AuthUser $authUser, SlugRedirect $slugRedirect): bool
    {
        return $authUser->can('Delete:SlugRedirect');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:SlugRedirect');
    }

    public function restore(AuthUser $authUser, SlugRedirect $slugRedirect): bool
    {
        return $authUser->can('Restore:SlugRedirect');
    }

    public function forceDelete(AuthUser $authUser, SlugRedirect $slugRedirect): bool
    {
        return $authUser->can('ForceDelete:SlugRedirect');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:SlugRedirect');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:SlugRedirect');
    }

    public function replicate(AuthUser $authUser, SlugRedirect $slugRedirect): bool
    {
        return $authUser->can('Replicate:SlugRedirect');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:SlugRedirect');
    }

}