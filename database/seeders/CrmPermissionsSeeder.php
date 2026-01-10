<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrmPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get Owner role ID (should be 1)
        $ownerRole = DB::table('roles')->where('name', 'Owner')->first();
        $ownerRoleId = $ownerRole ? $ownerRole->id : 1;
        
        // Get max permission_role ID to continue from
        $maxPermissionRoleId = DB::table('permission_role')->max('id') ?? 0;
        $permissionRoleId = $maxPermissionRoleId + 1;
        
        // Define all CRM permissions with their desired IDs
        $crmPermissions = [
            ['id' => 191, 'name' => 'crm_pipelines_view'],
            ['id' => 192, 'name' => 'crm_pipelines_add'],
            ['id' => 193, 'name' => 'crm_pipelines_edit'],
            ['id' => 194, 'name' => 'crm_pipelines_delete'],
            ['id' => 195, 'name' => 'crm_deals_view'],
            ['id' => 196, 'name' => 'crm_deals_add'],
            ['id' => 197, 'name' => 'crm_deals_edit'],
            ['id' => 198, 'name' => 'crm_deals_delete'],
            ['id' => 199, 'name' => 'crm_deals_assign'],
            ['id' => 200, 'name' => 'crm_followups_view'],
            ['id' => 201, 'name' => 'crm_followups_add'],
            ['id' => 202, 'name' => 'crm_followups_edit'],
            ['id' => 203, 'name' => 'crm_followups_delete'],
            ['id' => 204, 'name' => 'crm_followups_complete'],
            ['id' => 205, 'name' => 'crm_forms_view'],
            ['id' => 206, 'name' => 'crm_forms_add'],
            ['id' => 207, 'name' => 'crm_forms_edit'],
            ['id' => 208, 'name' => 'crm_forms_delete'],
            ['id' => 209, 'name' => 'crm_forms_publish'],
            ['id' => 210, 'name' => 'crm_form_submissions_view'],
            ['id' => 211, 'name' => 'crm_form_submissions_delete'],
            ['id' => 212, 'name' => 'crm_form_submissions_export'],
            ['id' => 213, 'name' => 'crm_form_submissions_match_contact'],
            ['id' => 214, 'name' => 'crm_contact_groups_view'],
            ['id' => 215, 'name' => 'crm_contact_groups_add'],
            ['id' => 216, 'name' => 'crm_contact_groups_edit'],
            ['id' => 217, 'name' => 'crm_contact_groups_delete'],
            ['id' => 218, 'name' => 'crm_tags_view'],
            ['id' => 219, 'name' => 'crm_tags_add'],
            ['id' => 220, 'name' => 'crm_tags_edit'],
            ['id' => 221, 'name' => 'crm_tags_delete'],
            ['id' => 222, 'name' => 'crm_contacts_view'],
            ['id' => 223, 'name' => 'crm_contacts_add'],
            ['id' => 224, 'name' => 'crm_contacts_edit'],
            ['id' => 225, 'name' => 'crm_contacts_delete'],
            ['id' => 226, 'name' => 'crm_contacts_assign_agent'],
        ];
        
        // Create missing permissions (check by name, use desired ID if available)
        $permissionsToInsert = [];
        $permissionIdMap = []; // Map permission name to its ID
        
        foreach ($crmPermissions as $perm) {
            $existing = DB::table('permissions')->where('name', $perm['name'])->first();
            if (!$existing) {
                // Check if the desired ID is available
                $idInUse = DB::table('permissions')->where('id', $perm['id'])->first();
                if ($idInUse) {
                    // ID is taken, use auto-increment (let DB assign ID)
                    $insertedId = DB::table('permissions')->insertGetId(['name' => $perm['name']]);
                    $permissionIdMap[$perm['name']] = $insertedId;
                } else {
                    // Use desired ID
                    DB::table('permissions')->insert([$perm]);
                    $permissionIdMap[$perm['name']] = $perm['id'];
                }
            } else {
                $permissionIdMap[$perm['name']] = $existing->id;
            }
        }
        
        // Assign all CRM permissions to Owner role
        $permissionRoleData = [];
        foreach ($permissionIdMap as $permissionName => $permissionId) {
            // Check if this permission-role assignment already exists
            $existingAssignment = DB::table('permission_role')
                ->where('permission_id', $permissionId)
                ->where('role_id', $ownerRoleId)
                ->first();
            
            if (!$existingAssignment) {
                $permissionRoleData[] = [
                    'id' => $permissionRoleId++,
                    'permission_id' => $permissionId,
                    'role_id' => $ownerRoleId,
                ];
            }
        }
        if (!empty($permissionRoleData)) {
            DB::table('permission_role')->insert($permissionRoleData);
        }

        // Check if crm_agent role exists, if not create it
        $crmAgentRole = DB::table('roles')->where('name', 'crm_agent')->first();
        $crmAgentRoleId = null;

        if (!$crmAgentRole) {
            // Get max role ID
            $maxRoleId = DB::table('roles')->max('id') ?? 0;
            $crmAgentRoleId = $maxRoleId + 1;

            // Create crm_agent role
            DB::table('roles')->insert([
                'id' => $crmAgentRoleId,
                'name' => 'crm_agent',
                'label' => 'CRM Agent',
                'status' => 1,
                'description' => 'CRM Agent with access to pipelines, deals, followups, forms, contact groups, and tags',
            ]);
        } else {
            $crmAgentRoleId = $crmAgentRole->id;
        }

        // Assign all CRM permissions to crm_agent role
        $crmAgentPermissionRoleData = [];
        foreach ($permissionIdMap as $permissionName => $permissionId) {
            // Check if this permission-role assignment already exists
            $existingAssignment = DB::table('permission_role')
                ->where('permission_id', $permissionId)
                ->where('role_id', $crmAgentRoleId)
                ->first();
            
            if (!$existingAssignment) {
                $crmAgentPermissionRoleData[] = [
                    'id' => $permissionRoleId++,
                    'permission_id' => $permissionId,
                    'role_id' => $crmAgentRoleId,
                ];
            }
        }
        if (!empty($crmAgentPermissionRoleData)) {
            DB::table('permission_role')->insert($crmAgentPermissionRoleData);
        }
    }
}
