<?php

namespace App\Policies;

use App\Models\Followup;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FollowupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        $permission = Permission::where('name', 'crm_followups_view')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Followup $followup)
    {
        $permission = Permission::where('name', 'crm_followups_view')->first();
        if (!$permission) return false;
        
        // Users can view their own followups or if they have permission
        return $user->hasRole($permission->roles) || $followup->assigned_to === $user->id || $followup->created_by === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        $permission = Permission::where('name', 'crm_followups_add')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Followup $followup)
    {
        $permission = Permission::where('name', 'crm_followups_edit')->first();
        if (!$permission) return false;
        
        return $user->hasRole($permission->roles) || $followup->assigned_to === $user->id || $followup->created_by === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Followup $followup)
    {
        $permission = Permission::where('name', 'crm_followups_delete')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }
}
