<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRequest;
use App\Http\Requests\Admin\UpdateCompanyRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Http\RedirectResponse;

class AdminCompanyPageController extends Controller
{
    public function __invoke(Request $request): View|JsonResponse
    {
        abort_unless(Auth::user()?->isSuperAdmin(), 403);

        $companies = Company::withCount('users')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search');
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('domain', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        $editingCompany = null;
        if ($request->filled('edit')) {
            $editingCompany = Company::findOrFail($request->integer('edit'));
        }

        $viewData = [
            'companies' => $companies,
            'user' => Auth::user(),
            'modalMode' => $request->query('modal'),
            'editingCompany' => $editingCompany,
        ];

        if ($request->ajax()) {
            return response()->json([
                'tableHtml' => view('admin.companies.table.index', $viewData)->render(),
                'modalHtml' => view('admin.companies.modal.form', $viewData)->render(),
            ]);
        }

        return view('admin.companies.index', $viewData);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.companies', ['modal' => 'create']);
    }

    public function store(StoreCompanyRequest $request): RedirectResponse|JsonResponse
    {
        abort_unless(Auth::user()?->isSuperAdmin(), 403);

        $payload = $request->validated();

        DB::transaction(function () use ($payload) {
            $company = Company::create($payload);

            $temporaryPassword = Str::random(12);

            $user = User::create([
                'name' => $payload['owner_name'],
                'username' => $payload['owner_username'],
                'email' => $payload['owner_email'],
                'password' => Hash::make($temporaryPassword),
                'company_id' => $company->id,
                'email_verified_at' => now(),
            ]);

            $user->assignRole('Company Owner');

            session()->flash('status', "Sirket olusturuldu. Gecici sifre: {$temporaryPassword}");
        });

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => session('status', 'Sirket olusturuldu.'),
            ]);
        }

        return redirect()->route('admin.companies');
    }

    public function edit(Company $company): RedirectResponse
    {
        return redirect()->route('admin.companies', ['modal' => 'edit', 'edit' => $company->id]);
    }

    public function update(UpdateCompanyRequest $request, Company $company): RedirectResponse|JsonResponse
    {
        abort_unless(Auth::user()?->isSuperAdmin(), 403);

        $company->update($request->validated());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Sirket bilgileri guncellendi.',
            ]);
        }

        return redirect()->route('admin.companies')->with('status', 'Sirket bilgileri guncellendi.');
    }
}
