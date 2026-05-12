<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockAlert;
use App\Models\User;
use App\Notifications\StockLowLevelNotification;
use App\Repositories\Interfaces\StockAlertRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class StockAlertService
{
    public function __construct(protected StockAlertRepositoryInterface $repository) {}

    public function getActiveAlerts(array $filters = []): Collection
    {
        return $this->repository->getActiveAlerts($filters);
    }

    public function getAlerts(array $filters = []): Collection
    {
        return $this->repository->getAlerts($filters);
    }

    public function syncAlerts(?int $clinicId = null): int
    {
        $query = Stock::active()->with(['product', 'clinic']);
        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        $count = 0;
        foreach ($query->cursor() as $stock) {
            $this->checkAndCreateAlerts($stock);
            $count++;
        }

        return $count;
    }

    public function checkAndCreateAlerts(Stock $stock): array
    {
        $alerts = $this->checkAndGetAlerts($stock);
        $createdAlerts = [];

        foreach ($alerts as $alertData) {
            $createdAlerts[] = $this->repository->updateOrCreateActive(
                [
                    'stock_id' => $stock->id,
                    'type' => $alertData['type'],
                ],
                array_merge($alertData, [
                    'clinic_id' => $stock->clinic_id,
                    'product_id' => $stock->product_id,
                ])
            );
        }

        return $createdAlerts;
    }

    public function checkAndGetAlerts(Stock $stock): array
    {
        $alerts = [];
        $product = $stock->product;

        if (!$product) return [];

        // Low stock check
        $totalStock = $stock->current_stock;
        $redLevel = $product->red_alert_level ?? $product->critical_stock_level ?? 5;
        $yellowLevel = $product->yellow_alert_level ?? $product->min_stock_level ?? 10;

        if ($totalStock <= $redLevel) {
            $alerts[] = [
                'type' => 'critical_stock',
                'title' => 'Kritik Stok Uyarı',
                'message' => "{$product->name} stok seviyesi kritik: {$totalStock}",
                'current_stock_level' => $totalStock,
                'threshold_level' => $redLevel,
            ];
        } elseif ($totalStock <= $yellowLevel) {
            $alerts[] = [
                'type' => 'low_stock',
                'title' => 'Düşük Stok Uyarı',
                'message' => "{$product->name} stok seviyesi düşük: {$totalStock}",
                'current_stock_level' => $totalStock,
                'threshold_level' => $yellowLevel,
            ];
        }

        // Expiry check
        if ($stock->track_expiry && $stock->expiry_date) {
            $days = now()->diffInDays($stock->expiry_date, false);
            if ($days < 0) {
                $alerts[] = [
                    'type' => 'expired',
                    'title' => 'Süresi Geçmiş Ürün',
                    'message' => "{$product->name} süresi geçti.",
                    'expiry_date' => $stock->expiry_date,
                ];
            } elseif ($days <= ($stock->expiry_red_days ?? 15)) {
                $alerts[] = [
                    'type' => 'near_expiry',
                    'title' => 'Kritik Son Kullanma Tarihi',
                    'message' => "{$product->name} SKT kritik: {$days} gün kaldı.",
                    'expiry_date' => $stock->expiry_date,
                ];
            }
        }

        return $alerts;
    }

    public function getAlertById(int $id): ?StockAlert
    {
        return $this->repository->find($id);
    }

    public function resolveAlert(int $id, string $resolvedBy): bool
    {
        return (bool) $this->repository->update($id, [
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy,
            'is_active' => false,
        ]);
    }

    public function getAlertStatistics(?int $clinicId = null): array
    {
        return [
            'total_active' => $this->repository->countActiveAlerts($clinicId),
            'critical_stock' => $this->repository->countAlertsByType('critical_stock', $clinicId),
            'low_stock' => $this->repository->countAlertsByType('low_stock', $clinicId),
            'expired' => $this->repository->countAlertsByType('expired', $clinicId),
            'near_expiry' => $this->repository->countAlertsByType('near_expiry', $clinicId),
        ];
    }

    public function bulkResolve(array $ids, string $resolvedBy, ?string $notes = null): int
    {
        return $this->repository->bulkResolve($ids, $resolvedBy);
    }

    public function bulkDismiss(array $ids): int
    {
        return $this->repository->bulkResolve($ids, 'System (Dismissed)');
    }

    public function bulkDelete(array $ids): int
    {
        return $this->repository->bulkDeleteByIds($ids);
    }

    public function dismissAlert(int $id): bool
    {
        return $this->resolveAlert($id, 'System (Dismissed)');
    }

    public function deleteAlert(int $id): bool
    {
        return $this->repository->delete($id);
    }

    public function sendDigestNotification(array $lowStocks): void
    {
        // This is a placeholder for sending digest notifications
        // In a real app, you'd loop through users who should receive this
        $adminUsers = User::role('Admin')->get();
        // Notification::send($adminUsers, new StockAlertDigestNotification($lowStocks));
    }
}
