<?php

namespace App\Policies;

use App\Models\Deal;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DealPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        $permission = Permission::where('name', 'crm_deals_view')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Deal $deal)
    {
        $permission = Permission::where('name', 'crm_deals_view')->first();
        if (!$permission) return false;
        
        // Users can view their own deals or if they have permission
        return $user->hasRole($permission->roles) || $deal->assigned_to === $user->id || $deal->created_by === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        $permission = Permission::where('name', 'crm_deals_add')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Deal $deal)
    {
        $permission = Permission::where('name', 'crm_deals_edit')->first();
        if (!$permission) return false;
        
        // Users can edit their assigned deals or if they have permission
        return $user->hasRole($permission->roles) || $deal->assigned_to === $user->id || $deal->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Deal $deal)
    {
        $permission = Permission::where('name', 'crm_deals_delete')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }

    /**
     * Determine whether the user can assign deals.
     */
    public function assign(User $user)
    {
        $permission = Permission::where('name', 'crm_deals_edit')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }
}
