<?php

namespace App\Policies;

use App\Models\CrmForm;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CrmFormPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user)
    {
        $permission = Permission::where('name', 'crm_forms_view')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CrmForm $crmForm)
    {
        $permission = Permission::where('name', 'crm_forms_view')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user)
    {
        $permission = Permission::where('name', 'crm_forms_add')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CrmForm $crmForm)
    {
        $permission = Permission::where('name', 'crm_forms_edit')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CrmForm $crmForm)
    {
        $permission = Permission::where('name', 'crm_forms_delete')->first();
        return $permission ? $user->hasRole($permission->roles) : false;
    }
}
