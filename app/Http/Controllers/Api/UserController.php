<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use App\Models\Role;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use JsonResponseTrait;

    public function index(): JsonResponse
    {
        $user = Auth::user();
        
        // Super Admin ise tüm kullanıcıları görebilir, değilse sadece kendi şirketini
        $query = User::query();
        if (!$user->hasRole('Super Admin')) {
            $query->where('company_id', $user->company_id);
        }

        $users = $query->with('roles')->get();

        return $this->success($users, 'Users retrieved successfully.');
    }

    /**
     * Store a new user directly (without invitation).
     */
    public function store(\Illuminate\Http\Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role_id' => 'required|integer',
            'company_id' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return $this->error($validator->errors()->first(), 422);
        }

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

        // SECURITY: Protect the main "Company Owner" from being deleted (assuming Owner role name is 'Owner')
        // Note: This logic can be refined based on specific "Owner" identification criteria
        if ($userToDelete->hasRole('Owner')) {
            return $this->error('The Company Owner cannot be deleted.', 403);
        }

        $userToDelete->delete();

        return $this->success(null, 'User deleted successfully.');
    }
}
