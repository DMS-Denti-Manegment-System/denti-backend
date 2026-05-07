<?php

// app/Modules/Stock/Services/StockAlertService.php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockAlert;
use App\Repositories\Interfaces\StockAlertRepositoryInterface;
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
            $this->forceDeleteAlertsByProduct($stock->product_id);

            return;
        }

        // 🛡️ Ürün bazlı uyarı - önce ürünün mevcut uyarılarını temizle
        $this->forceDeleteAlertsByProduct($stock->product_id);

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
        if (! $stock->relationLoaded('product')) {
            $stock->load('product');
        }

        $product = $stock->product;

        // Pasif stoklar veya ürünü olmayan stoklar için uyarı üretme
        if (! $stock->is_active || ! $product) {
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
                'threshold_level' => $redLevel,
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
                'threshold_level' => $yellowLevel,
            ];
        }

        // 🛡️ ÜRÜN BAZLI SON KULLANMA TARİHİ KONTROLÜ
        // Tüm aktif batch'leri kontrol et, en kritik durumu tek uyarı olarak üret
        // Eager load edilmiş batches koleksiyonunu kullan (yeni DB sorgusu yapmamak için)
        $allBatches = $product->batches->filter(function ($batch) {
            return $batch->is_active && $batch->track_expiry && $batch->expiry_date !== null;
        });

        if ($allBatches->isNotEmpty()) {
            $today = now()->startOfDay();
            $mostUrgentBatch = null;
            $mostUrgentDays = PHP_INT_MAX;
            $hasExpired = false;

            foreach ($allBatches as $batch) {
                $daysToExpiry = (int) $today->diffInDays($batch->expiry_date, false);

                if ($daysToExpiry < 0) {
                    $hasExpired = true;
                    $mostUrgentBatch = $batch;
                    break;
                }

                if ($daysToExpiry < $mostUrgentDays) {
                    $mostUrgentDays = $daysToExpiry;
                    $mostUrgentBatch = $batch;
                }
            }

            if ($hasExpired && $mostUrgentBatch) {
                $alerts[] = [
                    'type' => 'expired',
                    'title' => 'Süresi Geçen Ürün',
                    'message' => "{$product->name} ürününün son kullanma tarihi geçmiştir! (Parti: #{$mostUrgentBatch->id})",
                    'expiry_date' => $mostUrgentBatch->expiry_date,
                ];
            } elseif ($mostUrgentBatch) {
                $redDays = $mostUrgentBatch->expiry_red_days ?? 10;
                $yellowDays = $mostUrgentBatch->expiry_yellow_days ?? 30;

                if ($mostUrgentDays <= $redDays) {
                    $alerts[] = [
                        'type' => 'critical_expiry',
                        'title' => 'Kritik Son Kullanma Tarihi',
                        'message' => "{$product->name} ürününün son kullanma tarihine çok az kaldı! Kalan: ".(int) $mostUrgentDays." gün (Parti: #{$mostUrgentBatch->id})",
                        'expiry_date' => $mostUrgentBatch->expiry_date,
                    ];
                } elseif ($mostUrgentDays <= $yellowDays) {
                    $alerts[] = [
                        'type' => 'near_expiry',
                        'title' => 'Son Kullanma Tarihi Yaklaşıyor',
                        'message' => "{$product->name} ürününün son kullanma tarihi yaklaşıyor. Kalan: ".(int) $mostUrgentDays." gün (Parti: #{$mostUrgentBatch->id})",
                        'expiry_date' => $mostUrgentBatch->expiry_date,
                    ];
                }
            }
        }

        return $alerts;
    }

    protected function createAlert(Stock $stock, array $alertData): StockAlert
    {
        // 🛡️ Ürün bazlı uyarı - her ürün için sadece 1 uyarı
        $alertData = array_merge($alertData, [
            'product_id' => $stock->product_id,
            'stock_id' => $stock->id,
            'clinic_id' => $stock->clinic_id,
            'is_active' => true,
            'is_resolved' => false,
        ]);

        return $this->stockAlertRepository->create($alertData);
    }

    public function resolveExistingAlerts(Stock $stock): void
    {
        $this->stockAlertRepository->resolveActiveAlerts($stock->id);
    }

    public function forceDeleteAlertsByStock(int $stockId): void
    {
        $this->stockAlertRepository->deleteActiveAlerts($stockId);
    }

    public function forceDeleteAlertsByProduct(int $productId): void
    {
        // 🛡️ Ürün bazlı uyarı temizleme - ürünün tüm uyarılarını sil
        $this->stockAlertRepository->deleteActiveAlertsByProduct($productId);
    }

    public function sendDigestNotification(int $companyId, array $items): void
    {
        $company = \App\Models\Company::find($companyId);
        if (! $company) {
            return;
        }

        // Şirket sahibini veya yöneticileri bul
        $users = \App\Models\User::where('company_id', $companyId)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['Company Owner', 'Admin', 'Clinic Manager']);
            })->get();

        if ($users->isEmpty()) {
            return;
        }

        // Özet bildirimi gönder
        Notification::send($users, new \App\Notifications\StockAlertDigestNotification($items));
    }

    protected function sendAlertNotification(StockAlert $alert): void
    {
        // Alarma ait ilişkileri yükle
        $alert->load(['clinic', 'stock.product']);
        $company = \App\Models\Company::find($alert->stock->company_id);

        // 1. Sistem kullanıcılarına (yöneticilere) bildirim gönder
        $users = \App\Models\User::where('company_id', $alert->stock->company_id)
            ->whereHas('roles', function ($q) {
                $q->whereIn('name', ['Company Owner', 'Admin', 'Stock Manager', 'Clinic Manager']);
            })->get();

        if ($users->isNotEmpty()) {
            \Illuminate\Support\Facades\Notification::send($users, new \App\Notifications\StockLowLevelNotification($alert));
        }

        // 2. Şirket bazlı ek e-posta adreslerine gönder
        if ($company && $company->alert_emails) {
            $emails = array_map('trim', explode(',', $company->alert_emails));
            $validEmails = array_filter($emails, fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL));

            if (! empty($validEmails)) {
                \Illuminate\Support\Facades\Notification::route('mail', $validEmails)
                    ->notify(new \App\Notifications\StockLowLevelNotification($alert));
            }
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

    public function getAlertById(int $id): ?StockAlert
    {
        return $this->stockAlertRepository->find($id);
    }

    /**
     * Tüm stokları tarayıp eksik uyarıları oluşturur.
     * Chunk kullanarak bellek verimliliği sağlar.
     */
    public function syncAlerts(?int $clinicId = null): int
    {
        $query = Stock::with(['product.batches']);
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

    public function resolveAlert(int $alertId, string $resolvedBy): bool
    {
        return (bool) $this->stockAlertRepository->update($alertId, [
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by' => $resolvedBy,
        ]);
    }

    public function getAlertStatistics(?int $clinicId = null): array
    {
        return [
            'total_active' => $this->stockAlertRepository->countActiveAlerts($clinicId),
            'low_stock' => $this->stockAlertRepository->countAlertsByType('low_stock', $clinicId),
            'critical_stock' => $this->stockAlertRepository->countAlertsByType('critical_stock', $clinicId),
            'expired' => $this->stockAlertRepository->countAlertsByType('expired', $clinicId),
            'near_expiry' => $this->stockAlertRepository->countAlertsByType('near_expiry', $clinicId),
        ];
    }

    public function getPendingCount(?int $clinicId = null): int
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

    public function bulkResolve(array $ids, string $resolvedBy, ?string $resolutionNotes = null): int
    {
        if ($ids === []) {
            return 0;
        }

        return $this->stockAlertRepository->bulkResolve($ids, $resolvedBy);
    }

    public function bulkDismiss(array $ids): int
    {
        return $this->stockAlertRepository->bulkDeleteByIds($ids);
    }

    public function bulkDelete(array $ids): int
    {
        return $this->stockAlertRepository->bulkDeleteByIds($ids);
    }
}
