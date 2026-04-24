<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use App\Models\Role;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use JsonResponseTrait;

    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Super Admin ise tüm kullanıcıları görebilir, değilse sadece kendi şirketini
        $query = User::query();
        if (!$user->hasRole(User::ROLE_SUPER_ADMIN)) {
            $query->where('company_id', $user->company_id);
        }

        // Frontend'den gelen arama (search) parametresi
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $users = $query->with('roles')->paginate($perPage);

        return $this->success($users, 'Users retrieved successfully.');
    }

    /**
     * Store a new user directly (without invitation).
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $currentUser = Auth::user();
        $companyId = $request->company_id ?? $currentUser->company_id;

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'company_id' => $companyId,
            'is_active' => true,
        ]);

        // Rol atama (Global rolleri de destekle)
        $role = Role::withoutGlobalScopes()
            ->where(function($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->find($request->role_id);

        if ($role) {
            $user->assignRole($role->name);
        }

        return $this->success($user->load('roles'), 'User created successfully.', 201);
    }

    /**
     * Display the specified user.
     */
    public function show(int $id): JsonResponse
    {
        $companyId = Auth::user()->company_id;

        $user = User::where('company_id', $companyId)
            ->with('roles')
            ->find($id);

        if (!$user) {
            return $this->error('User not found or unauthorized access.', 404);
        }

        return $this->success($user, 'User details retrieved successfully.');
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        if (!Auth::user()->can('manage-users')) {
            return $this->error('Bu işlemi yapmaya yetkiniz yok.', 403);
        }

        $companyId = Auth::user()->company_id;

        $user = User::where('company_id', $companyId)->find($id);

        if (!$user) {
            return $this->error('User not found or unauthorized access.', 404);
        }

        // Update user details
        $user->update($request->only(['name', 'is_active']));

        // Sync roles (Global rolleri de destekle)
        $role = Role::withoutGlobalScopes()
            ->where(function($q) use ($companyId) {
                $q->where('company_id', $companyId)->orWhereNull('company_id');
            })
            ->where('id', $request->role_id)
            ->first();

        if ($role) {
            $user->syncRoles([$role->name]);
        }

        return $this->success($user->load('roles'), 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        if (!Auth::user()->can('manage-users')) {
            return $this->error('Bu işlemi yapmaya yetkiniz yok.', 403);
        }

        $currentUser = Auth::user();
        $companyId = $currentUser->company_id;

        $userToDelete = User::where('company_id', $companyId)->find($id);

        if (!$userToDelete) {
            return $this->error('User not found or unauthorized access.', 404);
        }

        // SECURITY: Prevent the authenticated user from deleting themselves
        if ($currentUser->id === $userToDelete->id) {
            return $this->error('You cannot delete your own account.', 403);
        }

        // SECURITY: Protect the main "Company Owner" from being deleted
        if ($userToDelete->hasRole(User::ROLE_OWNER)) {
            return $this->error('The Company Owner cannot be deleted.', 403);
        }

        $userToDelete->delete();

        return $this->success(null, 'User deleted successfully.');
    }
}
