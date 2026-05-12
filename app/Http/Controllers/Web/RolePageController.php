<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class RolePageController extends Controller
{
    public function __invoke(Request $request): View|JsonResponse
    {
        $roles = Role::query()
            ->with('permissions')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhereHas('permissions', fn ($permissionQuery) => $permissionQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('name')
            ->get();

        $editingRole = null;
        $selectedPermissions = [];
        if ($request->filled('edit')) {
            $editingRole = Role::with('permissions')->findOrFail($request->route('role', $request->integer('edit')) ?? $request->integer('edit'));
            $selectedPermissions = $editingRole->permissions->pluck('name')->all();
        }

        $viewData = [
            'roles' => $roles,
            'permissions' => Permission::query()->orderBy('name')->get(),
            'modalMode' => $request->query('modal'),
            'editingRole' => $editingRole,
            'selectedPermissions' => $selectedPermissions,
        ];

        if ($request->ajax()) {
            return response()->json([
                'tableHtml' => view('operations.roles.table.index', $viewData)->render(),
                'modalHtml' => view('operations.roles.modal.form', $viewData)->render(),
            ]);
        }

        return view('operations.roles.index', $viewData);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('roles.index', ['modal' => 'create']);
    }

    public function store(StoreRoleRequest $request): RedirectResponse|JsonResponse
    {
        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        $requestedPermissions = $request->permissions;
        $role->syncPermissions($requestedPermissions);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Rol olusturuldu.',
            ]);
        }

        return redirect()->route('roles.index')->with('status', 'Rol olusturuldu.');
    }

    public function edit(Role $role): RedirectResponse
    {
        return redirect()->route('roles.index', ['modal' => 'edit', 'edit' => $role->id]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse|JsonResponse
    {
        $role->update(['name' => $request->name]);

        $requestedPermissions = $request->permissions;
        $role->syncPermissions($requestedPermissions);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Rol guncellendi.',
            ]);
        }

        return redirect()->route('roles.index')->with('status', 'Rol guncellendi.');
    }
}
