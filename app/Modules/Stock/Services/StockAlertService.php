<?php
// app/Modules/Stock/Services/StockAlertService.php

namespace App\Modules\Stock\Services;

use App\Modules\Stock\Repositories\Interfaces\StockAlertRepositoryInterface;
use App\Modules\Stock\Models\Stock;
use App\Modules\Stock\Models\StockAlert;
use App\Modules\Stock\Notifications\StockLowLevelNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class StockAlertService
{
    protected $stockAlertRepository;

    public function __construct(StockAlertRepositoryInterface $stockAlertRepository)
    {
        $this->stockAlertRepository = $stockAlertRepository;
    }

    public function checkAndCreateAlerts(Stock $stock): void
    {
        // Pasif stoklar için uyarı üretme
        if (!$stock->is_active) {
            $this->forceDeleteAlertsByStock($stock->id);
            return;
        }

        // Mevcut alarmları tamamen temizle (Yeni kural: direkt sil)
        $this->forceDeleteAlertsByStock($stock->id);

        $alerts = [];
        $currentValue = $stock->total_base_units;

        // Seviye değerlerini al (Null ise varsayılanlara düş)
        $yellowLevel = $stock->yellow_alert_level ?? $stock->min_stock_level ?? 10;
        $redLevel = $stock->red_alert_level ?? $stock->critical_stock_level ?? 5;

        // 1. Kritik Stok Kontrolü (Öncelikli)
        if ($currentValue <= $redLevel) {
            $unitName = $stock->has_sub_unit ? $stock->sub_unit_name : $stock->unit;
            $alerts[] = [
                'type' => 'critical_stock',
                'title' => 'Kritik Stok Seviyesi',
                'message' => "{$stock->name} için kritik stok seviyesine ulaşıldı. Mevcut: {$currentValue} {$unitName}",
                'current_stock_level' => $currentValue,
                'threshold_level' => $redLevel
            ];
        }
        // 2. Düşük Stok Kontrolü (Sadece kritik değilse)
        elseif ($currentValue <= $yellowLevel) {
            $unitName = $stock->has_sub_unit ? $stock->sub_unit_name : $stock->unit;
            $alerts[] = [
                'type' => 'low_stock',
                'title' => 'Düşük Stok Seviyesi',
                'message' => "{$stock->name} stok miktarı azaldı. Mevcut: {$currentValue} {$unitName}",
                'current_stock_level' => $currentValue,
                'threshold_level' => $yellowLevel
            ];
        }

        // Son kullanma tarihi kontrolü
        if ($stock->track_expiry && $stock->expiry_date) {
            $daysToExpiry = now()->diffInDays($stock->expiry_date, false);

            if ($daysToExpiry < 0) {
                // Süresi geçmiş
                $alerts[] = [
                    'type' => 'expired',
                    'title' => 'Süresi Geçen Ürün',
                    'message' => "{$stock->name} ürününün son kullanma tarihi geçmiştir!",
                    'expiry_date' => $stock->expiry_date
                ];
            } elseif ($daysToExpiry <= 30) {
                // Süresi yaklaşan
                $alerts[] = [
                    'type' => 'near_expiry',
                    'title' => 'Son Kullanma Tarihi Yaklaşıyor',
                    'message' => "{$stock->name} ürününün son kullanma tarihi yaklaşıyor. Kalan: {$daysToExpiry} gün",
                    'expiry_date' => $stock->expiry_date
                ];
            }
        }

        // Alarmları oluştur
        foreach ($alerts as $alertData) {
            $this->createAlert($stock, $alertData);
        }
    }

    protected function createAlert(Stock $stock, array $alertData): StockAlert
    {
        $alertData = array_merge($alertData, [
            'stock_id' => $stock->id,
            'clinic_id' => $stock->clinic_id,
            'is_active' => true,
            'is_resolved' => false
        ]);

        $alert = $this->stockAlertRepository->create($alertData);

        // Bildirim gönder
        $this->sendAlertNotification($alert);

        return $alert;
    }

    public function resolveExistingAlerts(Stock $stock): void
    {
        $this->stockAlertRepository->resolveActiveAlerts($stock->id);
    }

    public function forceDeleteAlertsByStock(int $stockId): void
    {
        $this->stockAlertRepository->deleteActiveAlerts($stockId);
    }

    protected function sendAlertNotification(StockAlert $alert): void
    {
        // Klinik sorumlusuna bildirim gönder
        $clinic = $alert->clinic;
        if ($clinic->responsible_person) {
            // Burada notification gönderilebilir
        }
    }

    public function getActiveAlerts(array $filters = []): Collection
    {
        return $this->stockAlertRepository->getActiveAlerts($filters);
    }

    public function getAlerts(array $filters = []): Collection
    {
        return $this->stockAlertRepository->getAlerts($filters);
    }

    /**
     * Tüm stokları tarayıp eksik uyarıları oluşturur.
     */
    public function syncAlerts(int $clinicId = null): int
    {
        $query = Stock::query();
        if ($clinicId) {
            $query->where('clinic_id', $clinicId);
        }

        $stocks = $query->get();
        $count = 0;

        foreach ($stocks as $stock) {
            $this->checkAndCreateAlerts($stock);
            $count++;
        }

        return $count;
    }

    public function resolveAlert(int $alertId, string $resolvedBy): bool
    {
        return (bool) $this->stockAlertRepository->update($alertId, [
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy
        ]);
    }

    public function getAlertStatistics(int $clinicId = null): array
    {
        return [
            'total_active' => $this->stockAlertRepository->countActiveAlerts($clinicId),
            'low_stock' => $this->stockAlertRepository->countAlertsByType('low_stock', $clinicId),
            'critical_stock' => $this->stockAlertRepository->countAlertsByType('critical_stock', $clinicId),
            'expired' => $this->stockAlertRepository->countAlertsByType('expired', $clinicId),
            'near_expiry' => $this->stockAlertRepository->countAlertsByType('near_expiry', $clinicId)
        ];
    }

    public function getPendingCount(int $clinicId = null): int
    {
        return $this->stockAlertRepository->countActiveAlerts($clinicId);
    }

    public function dismissAlert(int $alertId): bool
    {
        // Dismiss de artık direkt silebilir istersen ama genelde yoksayma pasife çekmektir.
        // Ancak talep "direkt silinsin" olduğu için bunu da delete'e çekebiliriz.
        return $this->deleteAlert($alertId);
    }

    public function deleteAlert(int $alertId): bool
    {
        return $this->stockAlertRepository->delete($alertId);
    }

    public function bulkResolve(array $ids, string $resolvedBy): int
    {
        $count = 0;
        foreach ($ids as $id) {
            if ($this->resolveAlert($id, $resolvedBy)) {
                $count++;
            }
        }
        return $count;
    }

    public function bulkDismiss(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            if ($this->dismissAlert($id)) {
                $count++;
            }
        }
        return $count;
    }

    public function bulkDelete(array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            if ($this->deleteAlert($id)) {
                $count++;
            }
        }
        return $count;
    }
}
