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
        // Mevcut aktif alarmları temizle
        $this->resolveExistingAlerts($stock);

        $alerts = [];

        // Düşük stok kontrolü (Sarı alarm)
        if ($stock->current_stock <= $stock->yellow_alert_level &&
            $stock->current_stock > $stock->red_alert_level) {
            $alerts[] = [
                'type' => 'low_stock',
                'title' => 'Düşük Stok Uyarısı',
                'message' => "{$stock->name} stoku azalmıştır. Mevcut: {$stock->current_stock} {$stock->unit}",
                'current_stock_level' => $stock->current_stock,
                'threshold_level' => $stock->yellow_alert_level
            ];
        }

        // Kritik stok kontrolü (Kırmızı alarm)
        if ($stock->current_stock <= $stock->red_alert_level) {
            $alerts[] = [
                'type' => 'critical_stock',
                'title' => 'Kritik Stok Uyarısı',
                'message' => "{$stock->name} stoku kritik seviyede! Mevcut: {$stock->current_stock} {$stock->unit}",
                'current_stock_level' => $stock->current_stock,
                'threshold_level' => $stock->red_alert_level
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

    protected function resolveExistingAlerts(Stock $stock): void
    {
        $this->stockAlertRepository->resolveActiveAlerts($stock->id);
    }

    protected function sendAlertNotification(StockAlert $alert): void
    {
        // Klinik sorumlusuna bildirim gönder
        $clinic = $alert->clinic;
        if ($clinic->responsible_person) {
            // Burada notification gönderilebilir
            // Notification::send($users, new StockLowLevelNotification($alert));
        }
    }

    public function getActiveAlerts(int $clinicId = null, string $type = null): Collection
    {
        return $this->stockAlertRepository->getActiveAlerts($clinicId, $type);
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
}