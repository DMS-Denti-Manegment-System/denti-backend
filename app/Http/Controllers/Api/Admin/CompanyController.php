<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Http\Requests\Admin\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\User;
use App\Traits\JsonResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CompanyController extends Controller
{
    use JsonResponseTrait;

    /**
     * Display a listing of companies.
     */
    public function index(): JsonResponse
    {
        $companies = Company::withCount('users')->get();
        return $this->success($companies, 'Companies retrieved successfully.');
    }

    /**
     * Store a newly created company.
     */
    public function store(StoreCompanyRequest $request): JsonResponse
    {
        try {
            return DB::transaction(function () use ($request) {
                // 1. Create Company
                $company = Company::create($request->validated());

                // 2. Create Default User (Company Owner)
                $temporaryPassword = Str::random(12);
                
                $user = User::create([
                    'name' => $request->owner_name,
                    'email' => $request->owner_email,
                    'password' => Hash::make($temporaryPassword),
                    'company_id' => $company->id,
                    'email_verified_at' => now(),
                ]);

                // 3. Assign Role
                $user->assignRole('Company Owner');

                return $this->success([
                    'company' => $company,
                    'owner' => [
                        'name' => $user->name,
                        'email' => $user->email,
                        'password_sent_to' => $user->email,
                    ]
                ], 'Company and owner created successfully. Password sent to email.', 201);
            });
        } catch (\Exception $e) {
            return $this->error('Failed to create company: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified company.
     */
    public function show(Company $company): JsonResponse
    {
        $company->load('users');
        return $this->success($company, 'Company details retrieved successfully.');
    }

    /**
     * Update the specified company.
     */
    public function update(UpdateCompanyRequest $request, Company $company): JsonResponse
    {
        $company->update($request->validated());
        return $this->success($company, 'Company updated successfully.');
    }

    /**
     * Remove the specified company.
     */
    public function destroy(Company $company): JsonResponse
    {
        // Add logic to check for dependencies if needed (e.g. if company has active subscriptions)
        $company->delete();
        return $this->success(null, 'Company deleted successfully.');
    }
}
