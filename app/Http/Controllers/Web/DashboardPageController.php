<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Clinic;
use App\Models\Company;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;

class DashboardPageController extends Controller
{
    public function __invoke(): View
    {
        $user = Auth::user();
        $companyId = $user->company_id;

        abort_unless($companyId || $user->isSuperAdmin(), 404, 'Sirket bilgisi bulunamadi.');

        $cacheKey = $user->isSuperAdmin() ? 'admin_dashboard_stats_blade' : "dashboard_stats_blade_{$companyId}";

        $stats = Cache::remember($cacheKey, 600, function () use ($user, $companyId) {
            $doctorRoleExists = Role::query()
                ->where('guard_name', 'web')
                ->where('name', 'Doctor')
                ->exists();

            if ($user->isSuperAdmin()) {
                return [
                    'company_name' => 'Sistem Yonetimi',
                    'total_users' => User::count(),
                    'total_doctors' => $doctorRoleExists ? User::role('Doctor')->count() : 0,
                    'total_employees' => User::count(),
                    'total_stock_items' => Product::count(),
                    'total_clinics' => Clinic::count(),
                    'total_suppliers' => Supplier::count(),
                    'total_companies' => Company::count(),
                    'is_super_admin' => true,
                ];
            }

            return [
                'company_name' => $user->company?->name ?? 'Bilinmeyen Sirket',
                'total_users' => User::where('company_id', $companyId)->count(),
                'total_doctors' => $doctorRoleExists
                    ? User::where('company_id', $companyId)->role('Doctor')->count()
                    : 0,
                'total_employees' => User::where('company_id', $companyId)->count(),
                'total_stock_items' => Product::where('company_id', $companyId)->count(),
                'total_clinics' => Clinic::where('company_id', $companyId)->count(),
                'total_suppliers' => Supplier::where('company_id', $companyId)->count(),
                'is_super_admin' => false,
            ];
        });

        return view('dashboard.index', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }
}
