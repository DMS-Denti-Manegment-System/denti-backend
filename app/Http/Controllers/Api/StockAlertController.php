<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockAlert;
use App\Services\StockAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StockAlertController extends Controller
{
    public function __construct(protected StockAlertService $stockAlertService) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', StockAlert::class);

        $filters = $request->only(['clinic_id', 'type', 'severity', 'search', 'date_from', 'date_to']);
        $activeOnly = $request->boolean('active_only', true);

        $alerts = $activeOnly
            ? $this->stockAlertService->getActiveAlerts($filters)
            : $this->stockAlertService->getAlerts($filters);

        return $this->success($alerts);
    }

    public function sync(Request $request)
    {
        $clinicId = $request->integer('clinic_id') ?: null;
        $count = $this->stockAlertService->syncAlerts($clinicId);

        return $this->success(['processed_count' => $count], "{$count} urun tarandi ve uyarilar kontrol edildi.");
    }

    public function show($id)
    {
        $alert = $this->stockAlertService->getAlertById($id);

        if (! $alert) {
            return $this->error('Alarm bulunamadi', 404);
        }

        return $this->success($alert);
    }

    public function resolve(Request $request, $id)
    {
        $alert = $this->stockAlertService->getAlertById($id);
        $this->authorize('resolve', $alert);

        $validated = $request->validate([
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $result = $this->stockAlertService->resolveAlert((int) $id, auth()->user()->name);

        if (! $result) {
            return $this->error('Alarm cozumlenemedi', 400);
        }

        return $this->success(null, 'Alarm basariyla cozumlendi');
    }

    public function getStatistics(Request $request)
    {
        $this->authorize('viewAny', StockAlert::class);

        return $this->success($this->stockAlertService->getAlertStatistics($request->integer('clinic_id') ?: null));
    }

    public function getPendingCount(Request $request)
    {
        $this->authorize('viewAny', StockAlert::class);

        $clinicId = $request->integer('clinic_id') ?: null;
        $user = auth()->user();
        $companyId = $user->company_id;
        $cacheKey = "pending_alerts_count_{$companyId}_".($clinicId ?? 'all');

        $count = Cache::remember($cacheKey, 60, function () use ($companyId, $clinicId) {
            $query = StockAlert::where('company_id', $companyId)
                ->where('is_active', true)
                ->where('is_resolved', false);

            if ($clinicId) {
                $query->where('clinic_id', $clinicId);
            }

            return $query->count();
        });

        return $this->success(['count' => $count]);
    }

    public function getActive(Request $request)
    {
        $filters = $request->only(['clinic_id', 'type', 'severity', 'search', 'date_from', 'date_to']);

        return $this->success($this->stockAlertService->getActiveAlerts($filters));
    }

    public function getSettings(Request $request)
    {
        return $this->success([
            'email_notifications' => true,
            'push_notifications' => true,
            'daily_digest' => false,
        ]);
    }

    public function updateSettings(Request $request)
    {
        return $this->success($request->all(), 'Ayarlar guncellendi');
    }

    public function bulkResolve(Request $request)
    {
        $this->authorize('bulkResolve', StockAlert::class);

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'resolution_notes' => 'nullable|string|max:1000',
        ]);

        $count = $this->stockAlertService->bulkResolve($validated['ids'], auth()->user()->name, $validated['resolution_notes'] ?? null);

        return $this->success(['count' => $count], "{$count} alarm basariyla cozumlendi");
    }

    public function bulkDismiss(Request $request)
    {
        $this->authorize('bulkDismiss', StockAlert::class);

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $count = $this->stockAlertService->bulkDismiss($validated['ids']);

        return $this->success(['count' => $count], "{$count} alarm yoksayildi");
    }

    public function bulkDelete(Request $request)
    {
        $this->authorize('bulkDelete', StockAlert::class);

        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);

        $count = $this->stockAlertService->bulkDelete($validated['ids']);

        return $this->success(['count' => $count], "{$count} alarm silindi");
    }

    public function dismiss(Request $request, $id)
    {
        $alert = $this->stockAlertService->getAlertById($id);
        $this->authorize('dismiss', $alert);

        $result = $this->stockAlertService->dismissAlert($id);

        if (! $result) {
            return $this->error('Alarm yoksayilamadi', 400);
        }

        return $this->success(null, 'Alarm basariyla yoksayildi');
    }

    public function destroy($id)
    {
        $alert = $this->stockAlertService->getAlertById($id);
        $this->authorize('delete', $alert);

        $result = $this->stockAlertService->deleteAlert($id);

        if (! $result) {
            return $this->error('Alarm silinemedi', 400);
        }

        return $this->success(null, 'Alarm basariyla silindi');
    }
}
