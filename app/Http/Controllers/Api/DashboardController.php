<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DashboardStatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardStatsService $dashboardStatsService
    ) {}

    public function index(): JsonResponse
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(401, 'Unauthenticated');
        }
        $stats = $this->dashboardStatsService->getStatsForUser($user);

        return $this->success($stats, 'Dashboard stats retrieved successfully.');
    }
}
