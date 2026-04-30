<?php
// app/Modules/Stock/Services/StockAlertService.php

namespace App\Services;

use App\Repositories\Interfaces\StockAlertRepositoryInterface;
use App\Models\Stock;
use App\Models\StockAlert;
use App\Notifications\StockLowLevelNotification;
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
        $alerts = $this->calculateAlertsForStock($stock);
        
        if (empty($alerts)) {
            $this->forceDeleteAlertsByStock($stock->id);
            return;
        }

        // Mevcut alarmları tamamen temizle ve yenilerini ekle
        $this->forceDeleteAlertsByStock($stock->id);

        foreach ($alerts as $alertData) {
            $this->createAlert($stock, $alertData);
        }
    }

    /**
     * Sadece uyarıları hesaplar ama kaydetmez/bildirim atmaz.
     */
    public function checkAndGetAlerts(Stock $stock): array
    {
        return $this->calculateAlertsForStock($stock);
    }

    protected function calculateAlertsForStock(Stock $stock): array
    {
        // Ensure product is loaded
        if (!$stock->relationLoaded('product')) {
            $stock->load('product');
        }

        $product = $stock->product;

        // Pasif stoklar veya ürünü olmayan stoklar için uyarı üretme
        if (!$stock->is_active || !$product) {
            return [];
        }

        $alerts = [];
        // Toplam ürün stoğunu kontrol et
        $currentValue = $product->total_stock;

        // Seviye değerlerini üründen al
        $yellowLevel = $product->yellow_alert_level ?? $product->min_stock_level ?? 10;
        $redLevel = $product->red_alert_level ?? $product->critical_stock_level ?? 5;

        // 1. Kritik Stok Kontrolü
        if ($currentValue <= $redLevel) {
            $unitName = $product->unit;
            $alerts[] = [
                'type' => 'critical_stock',
                'title' => 'Kritik Stok Seviyesi',
                'message' => "{$product->name} için kritik stok seviyesine ulaşıldı. Toplam: {$currentValue} {$unitName}",
                'current_stock_level' => $currentValue,
                'threshold_level' => $redLevel
            ];
        }
        // 2. Düşük Stok Kontrolü
        elseif ($currentValue <= $yellowLevel) {
            $unitName = $product->unit;
            $alerts[] = [
                'type' => 'low_stock',
                'title' => 'Düşük Stok Seviyesi',
                'message' => "{$product->name} stok miktarı azaldı. Toplam: {$currentValue} {$unitName}",
                'current_stock_level' => $currentValue,
                'threshold_level' => $yellowLevel
            ];
        }

        // Son kullanma tarihi kontrolü
        if ($stock->track_expiry && $stock->expiry_date) {
            $daysToExpiry = now()->diffInDays($stock->expiry_date, false);
            $redDays = $stock->expiry_red_days ?? 10;
            $yellowDays = $stock->expiry_yellow_days ?? 30;

            if ($daysToExpiry < 0) {
                $alerts[] = [
                    'type' => 'expired',
                    'title' => 'Süresi Geçen Ürün',
                    'message' => "{$product->name} ürününün son kullanma tarihi geçmiştir! (Parti ID: #{$stock->id})",
                    'expiry_date' => $stock->expiry_date
                ];
            } elseif ($daysToExpiry <= $redDays) {
                $alerts[] = [
                    'type' => 'critical_expiry',
                    'title' => 'Kritik Son Kullanma Tarihi',
                    'message' => "{$product->name} ürününün son kullanma tarihine çok az kaldı! Kalan: {$daysToExpiry} gün",
                    'expiry_date' => $stock->expiry_date
                ];
            } elseif ($daysToExpiry <= $yellowDays) {
                $alerts[] = [
                    'type' => 'near_expiry',
                    'title' => 'Son Kullanma Tarihi Yaklaşıyor',
                    'message' => "{$product->name} ürününün son kullanma tarihi yaklaşıyor. Kalan: {$daysToExpiry} gün",
                    'expiry_date' => $stock->expiry_date
                ];
            }
        }

        return $alerts;
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

    public function sendDigestNotification(int $companyId, array $items): void
    {
        $company = \App\Models\Company::find($companyId);
        if (!$company) return;

        // Şirket sahibini veya yöneticileri bul
        $users = \App\Models\User::where('company_id', $companyId)
            ->whereHas('roles', function($q) {
                $q->whereIn('name', ['Owner', 'Admin']);
            })->get();

        if ($users->isEmpty()) return;

        // Özet bildirimi gönder
        Notification::send($users, new \App\Notifications\StockAlertDigestNotification($items));
    }

    protected function sendAlertNotification(StockAlert $alert): void
    {
        // Alarma ait ilişkileri yükle
        $alert->load(['clinic', 'stock.product']);

        // Şirket sahibi ve yöneticileri bul
        $users = \App\Models\User::where('company_id', $alert->stock->company_id)
            ->whereHas('roles', function($q) {
                $q->whereIn('name', ['Owner', 'Admin', 'Stock Manager']);
            })->get();

        if ($users->isNotEmpty()) {
            \Illuminate\Support\Facades\Notification::send($users, new \App\Notifications\StockLowLevelNotification($alert));
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
