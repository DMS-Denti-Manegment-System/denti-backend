<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index(\Illuminate\Http\Request $request): JsonResponse
    {
        $query = User::query();

        // Frontend'den gelen arama (search) parametresi
        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $users = $query->with(['permissions', 'clinic'])->paginate($perPage);

        return $this->success($users, 'Users retrieved successfully.');
    }

    /**
     * Store a new user directly (without invitation).
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $currentUser = Auth::user();

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'clinic_id' => $request->clinic_id,
            'is_active' => true,
        ]);

        $permissions = PermissionCatalog::sanitizeForAssigner(
            (array) $request->input('permissions', []),
            $currentUser
        );
        $user->syncPermissions($permissions);

        return $this->success($user->load('permissions'), 'User created successfully.', 201);
    }

    /**
     * Display the specified user.
     */
    public function show(int $id): JsonResponse
    {
        $user = User::with('permissions')->find($id);

        if (! $user) {
            return $this->error('User not found or unauthorized access.', 404);
        }

        return $this->success($user, 'User details retrieved successfully.');
    }

    /**
     * Update the specified user in storage.
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        if (! Auth::user()->can('manage-users')) {
            return $this->error('Bu işlemi yapmaya yetkiniz yok.', 403);
        }

        $currentUser = Auth::user();
        $user = User::query()->find($id);

        if (! $user) {
            return $this->error('User not found or unauthorized access.', 404);
        }

        // Update user details
        $user->update($request->only(['name', 'is_active', 'clinic_id']));

        $permissions = PermissionCatalog::sanitizeForAssigner(
            (array) $request->input('permissions', []),
            $currentUser
        );
        $user->syncPermissions($permissions);

        return $this->success($user->load('permissions'), 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        if (! Auth::user()->can('manage-users')) {
            return $this->error('Bu işlemi yapmaya yetkiniz yok.', 403);
        }

        $currentUser = Auth::user();
        $userToDelete = User::query()->find($id);

        if (! $userToDelete) {
            return $this->error('User not found or unauthorized access.', 404);
        }

        // SECURITY: Prevent the authenticated user from deleting themselves
        if ($currentUser->id === $userToDelete->id) {
            return $this->error('You cannot delete your own account.', 403);
        }

        // SECURITY: Protect the main admin from being deleted
        if ($userToDelete->hasRole(User::ROLE_OWNER)) {
            return $this->error('The main admin cannot be deleted.', 403);
        }

        $userToDelete->delete();

        return $this->success(null, 'User deleted successfully.');
    }
}
