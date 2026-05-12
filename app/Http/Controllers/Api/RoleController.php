<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    public function index(): JsonResponse
    {
        $roles = Role::query()->with('permissions')->get();

        return $this->success($roles, 'Roles retrieved successfully.');
    }

    /**
     * List all available system permissions grouped by module.
     */
    public function permissions(): JsonResponse
    {
        $permissions = Permission::all();

        $grouped = $permissions->groupBy(function ($permission) {
            if (str_contains($permission->name, 'stocks')) {
                return 'Stocks';
            }
            if (str_contains($permission->name, 'clinics')) {
                return 'Clinics';
            }
            if (str_contains($permission->name, 'reports')) {
                return 'Reports';
            }
            if (str_contains($permission->name, 'users')) {
                return 'User Management';
            }
            if (str_contains($permission->name, 'company')) {
                return 'Company Management';
            }
            if (str_contains($permission->name, 'audit')) {
                return 'Logs';
            }

            return 'General';
        });

        // Frontend'in beklediği [ { module: '...', permissions: [] } ] formatına dönüştür
        $formatted = [];
        foreach ($grouped as $module => $perms) {
            $formatted[] = [
                'module' => $module,
                'permissions' => $perms,
            ];
        }

        return $this->success($formatted, 'Permissions retrieved successfully.');
    }

    /**
     * Store a new role and sync the selected permissions to it.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        $requestedPermissions = $request->permissions;
        $role->syncPermissions($requestedPermissions);

        return $this->success($role->load('permissions'), 'Role created successfully.', 201);
    }

    /**
     * Display the specified role.
     */
    public function show(Role $role): JsonResponse
    {
        return $this->success($role->load('permissions'), 'Role retrieved successfully.');
    }

    /**
     * Update the specified role and sync permissions.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $role->update([
            'name' => $request->name,
        ]);

        $requestedPermissions = $request->permissions;
        $role->syncPermissions($requestedPermissions);

        return $this->success($role->load('permissions'), 'Role updated successfully.');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): JsonResponse
    {
        if ($role->name === 'Admin') {
            return $this->error('System roles cannot be deleted.', 403);
        }

        $role->delete();

        return $this->success(null, 'Role deleted successfully.');
    }
}
