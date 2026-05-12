<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Web\Traits\HandlesOperationsResponses;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class EmployeeController extends Controller
{
    use HandlesOperationsResponses;

    public function index(Request $request): View|JsonResponse
    {
        $includeModalData = ! $request->ajax() || $request->query('modal') || $request->boolean('include_modal');
        $viewData = $this->getEmployeesViewData($request, $includeModalData);

        return $this->moduleResponse($request, 'operations.employees.index', $viewData, 'operations.employees.table.index', 'operations.employees.modal.form');
    }

    protected function getEmployeesViewData(Request $request, bool $includeModalData = false): array
    {
        $employees = User::query()
            ->with(['roles', 'clinic'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search');
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($this->perPage($request));

        $data = [
            'users' => $employees,
        ];

        if ($includeModalData) {
            $data['modalMode'] = $request->query('modal');
            $data['editingEmployee'] = $request->filled('edit') ? User::with('permissions')->find($request->integer('edit')) : null;
            $data['clinics'] = \App\Models\Clinic::query()->active()->orderBy('name')->get();
            $data['roles'] = Role::all();
            $data['permissionCrudMatrix'] = \App\Support\PermissionCatalog::crudMatrix();
            $data['permissionFeatureGroups'] = \App\Support\PermissionCatalog::featurePermissions();
        } else {
            $data['modalMode'] = null;
            $data['editingEmployee'] = null;
            $data['clinics'] = collect();
            $data['roles'] = collect();
            $data['permissionCrudMatrix'] = [];
            $data['permissionFeatureGroups'] = [];
        }

        return $data;
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('employees.index', ['modal' => 'create']);
    }

    public function store(StoreUserRequest $request): RedirectResponse|JsonResponse
    {
        $user = User::create([
            ...$request->validated(),
            'password' => bcrypt($request->password),
        ]);

        if ($request->filled('roles')) {
            $user->syncRoles($request->roles);
        }

        return $this->actionResponse($request, 'employees.index', 'Personel oluşturuldu.');
    }

    public function edit(User $user): RedirectResponse
    {
        return redirect()->route('employees.index', ['modal' => 'edit', 'edit' => $user->id]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse|JsonResponse
    {
        $user->update($request->validated());

        if ($request->filled('roles')) {
            $user->syncRoles($request->roles);
        }

        return $this->actionResponse($request, 'employees.index', 'Personel güncellendi.');
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Kendinizi silemezsiniz.'], 422);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'Personel silindi.']);
    }
}
