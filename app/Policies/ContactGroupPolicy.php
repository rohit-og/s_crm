<?php

namespace App\Policies;

use App\Models\ContactGroup;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ContactGroupPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        $permission = Permission::where('name', 'crm_contact_groups_view')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ContactGroup $contactGroup)
    {
        $permission = Permission::where('name', 'crm_contact_groups_view')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        $permission = Permission::where('name', 'crm_contact_groups_add')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ContactGroup $contactGroup)
    {
        $permission = Permission::where('name', 'crm_contact_groups_edit')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ContactGroup $contactGroup)
    {
        $permission = Permission::where('name', 'crm_contact_groups_delete')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }
}
