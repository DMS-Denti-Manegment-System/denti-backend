<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Stock;
use Illuminate\Pagination\LengthAwarePaginator;

class AlertService
{
    /**
     * Get products with alerts (low stock or near expiry) on the fly.
     */
    public function getDynamicAlerts(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Product::with(['batches' => fn ($q) => $q->active()])
            ->select('products.*')
            ->selectRaw('COALESCE((SELECT SUM(current_stock) FROM stocks WHERE stocks.product_id = products.id AND is_active = 1), 0) as total_stock');

        // Filter for "Alerts" only
        $query->where(function ($q) {
            // Low Stock Condition
            $q->whereRaw('COALESCE((SELECT SUM(current_stock) FROM stocks WHERE stocks.product_id = products.id AND is_active = 1), 0) <= COALESCE(red_alert_level, critical_stock_level)')
                ->orWhereRaw('COALESCE((SELECT SUM(current_stock) FROM stocks WHERE stocks.product_id = products.id AND is_active = 1), 0) <= COALESCE(yellow_alert_level, min_stock_level)')
              // Expiry Condition (at least one batch expired or near expiry)
                ->orWhereHas('batches', function ($batchQuery) {
                    $batchQuery->where('is_active', 1)
                        ->where('track_expiry', 1)
                        ->whereNotNull('expiry_date')
                        ->whereDate('expiry_date', '<=', now()->addDays(30));
                });
        });

        if (! empty($filters['search'])) {
            $search = '%'.$filters['search'].'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhereHas('batches', function ($bq) use ($search) {
                        $bq->where('batch_code', 'like', $search);
                    });
            });
        }

        $products = $query->latest()->paginate($perPage);

        // Transform products into "Alert" objects for the view
        $items = $products->getCollection()->flatMap(function ($product) use ($filters) {
            $alerts = [];
            $search = $filters['search'] ?? null;
            $totalStock = $product->total_stock;
            $redLevel = $product->red_alert_level ?? $product->critical_stock_level ?? 5;
            $yellowLevel = $product->yellow_alert_level ?? $product->min_stock_level ?? 10;

            // Only show stock alerts if not searching for a specific batch (or if search matches product name)
            if (! $search || str_contains(strtolower($product->name), strtolower($search))) {
                if ($totalStock <= $redLevel) {
                    $alerts[] = $this->formatAlert('critical_stock', 'Kritik Stok', "{$product->name} stok seviyesi kritik: {$totalStock}", $product);
                } elseif ($totalStock <= $yellowLevel) {
                    $alerts[] = $this->formatAlert('low_stock', 'Düşük Stok', "{$product->name} stok seviyesi düşük: {$totalStock}", $product);
                }
            }

            foreach ($product->batches as $batch) {
                if ($batch->track_expiry && $batch->expiry_date) {
                    // If searching, filter batches
                    if ($search && $batch->batch_code && ! str_contains(strtolower($batch->batch_code), strtolower($search)) && ! str_contains(strtolower($product->name), strtolower($search))) {
                        continue;
                    }

                    $days = now()->diffInDays($batch->expiry_date, false);
                    $batchLabel = $batch->batch_code ? "Parti {$batch->batch_code}" : "#{$batch->id}";
                    if ($days < 0) {
                        $alerts[] = $this->formatAlert('expired', 'Süresi Geçmiş', "{$product->name} ({$batchLabel}) süresi geçti.", $product, $batch);
                    } elseif ($days <= ($batch->expiry_red_days ?? 15)) {
                        $alerts[] = $this->formatAlert('critical_expiry', 'Kritik SKT', "{$product->name} ({$batchLabel}) SKT kritik: {$days} gün kaldı.", $product, $batch);
                    } elseif ($days <= ($batch->expiry_yellow_days ?? 30)) {
                        $alerts[] = $this->formatAlert('near_expiry', 'Yaklaşan SKT', "{$product->name} ({$batchLabel}) SKT yaklaşıyor: {$days} gün kaldı.", $product, $batch);
                    }
                }
            }

            return $alerts;
        });

        // Filter items by type if specified
        if (! empty($filters['type'])) {
            $items = $items->filter(fn ($item) => $item['type'] === $filters['type']);
        }

        return new LengthAwarePaginator(
            $items->values(), // values() to reset keys after filter
            $products->total(),
            $perPage,
            $products->currentPage(),
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    private function formatAlert(string $type, string $title, string $message, Product $product, ?Stock $batch = null): array
    {
        return [
            'id' => ($batch ? 'batch-'.$batch->id : 'prod-'.$product->id).'-'.$type,
            'type' => $type,
            'severity' => in_array($type, ['critical_stock', 'expired', 'critical_expiry']) ? 'critical' : 'warning',
            'title' => $title,
            'message' => $message,
            'product_name' => $product->name,
            'clinic_name' => $batch?->clinic->name ?? 'Genel',
            'created_at' => now(),
            'created_at_label' => now()->format('d.m.Y H:i'),
        ];
    }
}
