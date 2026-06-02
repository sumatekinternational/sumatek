<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the RBAC matrix (§3). Roles are created as GLOBAL (team_id null) so a
 * single definition is available to every agency; the per-agency scoping comes
 * from the team_id stored on each assignment.
 */
class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles/permissions in the global (team-less) context.
        app(PermissionRegistrar::class)->setPermissionsTeamId(null);

        $permissions = [
            'eligibility.check',
            'identity.read',
            'sponsor.view', 'sponsor.manage', 'sponsor.block',
            'worker.view', 'worker.manage',
            'contract.view', 'contract.manage',
            'invoice.view', 'invoice.manage',
            'report.view',
            'audit.view',
            'tenant.manage',
            'plan.manage',
            'block.moderate',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roles = [
            // Vendor control plane.
            'super-admin' => $permissions, // everything
            'vendor-support' => ['tenant.manage', 'block.moderate', 'audit.view'],
            'vendor-billing' => ['plan.manage', 'invoice.view'],

            // Agency plane.
            'agency-admin' => [
                'eligibility.check', 'identity.read',
                'sponsor.view', 'sponsor.manage', 'sponsor.block',
                'worker.view', 'worker.manage',
                'contract.view', 'contract.manage',
                'invoice.view', 'invoice.manage',
                'report.view', 'audit.view',
            ],
            'agency-manager' => [
                'eligibility.check', 'identity.read',
                'sponsor.view', 'sponsor.manage', 'sponsor.block',
                'worker.view', 'worker.manage',
                'contract.view', 'contract.manage',
                'report.view',
            ],
            'agency-staff' => [
                'eligibility.check', 'identity.read',
                'sponsor.view', 'sponsor.manage', 'sponsor.block',
                'worker.view', 'worker.manage',
            ],
            'agency-accountant' => ['invoice.view', 'invoice.manage', 'report.view'],
            'auditor' => [
                'sponsor.view', 'worker.view', 'contract.view',
                'invoice.view', 'report.view', 'audit.view',
            ],
        ];

        foreach ($roles as $role => $grants) {
            Role::findOrCreate($role, 'web')->syncPermissions($grants);
        }
    }
}
