<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DashboardStatsService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DashboardPageController extends Controller
{
    public function __construct(
        private readonly DashboardStatsService $dashboardStatsService
    ) {}

    public function __invoke(): View
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(401);
        }
        $stats = $this->dashboardStatsService->getStatsForUser($user);

        return view('dashboard.index', [
            'user' => $user,
            'stats' => $stats,
        ]);
    }
}
