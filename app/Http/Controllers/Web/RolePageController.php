<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class RolePageController extends Controller
{
    public function __invoke(Request $request): View
    {
        $roles = Role::withoutGlobalScopes()
            ->where(function ($query) {
                $query->where('company_id', auth()->user()->company_id)
                    ->orWhereNull('company_id');
            })
            ->with('permissions')
            ->orderBy('name')
            ->get();

        $editingRole = null;
        $selectedPermissions = [];
        if ($request->filled('edit')) {
            $editingRole = Role::with('permissions')->findOrFail($request->route('role', $request->integer('edit')) ?? $request->integer('edit'));
            $selectedPermissions = $editingRole->permissions->pluck('name')->all();
        }

        return view('operations.roles.index', [
            'roles' => $roles,
            'permissions' => Permission::query()->orderBy('name')->get(),
            'modalMode' => $request->query('modal'),
            'editingRole' => $editingRole,
            'selectedPermissions' => $selectedPermissions,
        ]);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('roles.index', ['modal' => 'create']);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
            'company_id' => auth()->user()->company_id,
        ]);

        $requestedPermissions = $request->permissions;
        if (!auth()->user()->hasRole('Super Admin')) {
            $userPermissions = auth()->user()->getAllPermissions()->pluck('name')->toArray();
            $requestedPermissions = collect($request->permissions)->intersect($userPermissions)->toArray();
        }
        $role->syncPermissions($requestedPermissions);

        return redirect()->route('roles.index')->with('status', 'Rol olusturuldu.');
    }

    public function edit(Role $role): RedirectResponse
    {
        abort_if($role->company_id !== null && $role->company_id !== auth()->user()->company_id, 403);
        abort_if($role->company_id === null && !auth()->user()->isSuperAdmin(), 403);

        return redirect()->route('roles.index', ['modal' => 'edit', 'edit' => $role->id]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->company_id !== null && $role->company_id !== auth()->user()->company_id, 403);
        abort_if($role->company_id === null && !auth()->user()->isSuperAdmin(), 403);

        $role->update(['name' => $request->name]);

        $requestedPermissions = $request->permissions;
        if (!auth()->user()->hasRole('Super Admin')) {
            $userPermissions = auth()->user()->getAllPermissions()->pluck('name')->toArray();
            $requestedPermissions = collect($request->permissions)->intersect($userPermissions)->toArray();
        }
        $role->syncPermissions($requestedPermissions);

        return redirect()->route('roles.index')->with('status', 'Rol guncellendi.');
    }
}
