<?php

namespace App\Observers;

use App\Mail\ExpiryAlert;
use App\Mail\LowStockAlert;
use App\Models\StockAlert;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class StockAlertObserver
{
    /**
     * Handle the StockAlert "created" event.
     */
    public function created(StockAlert $alert): void
    {
        // Sadece yeni oluşturulan uyarılar için mail gönder
        if ($alert->is_resolved) {
            return;
        }

        try {
            $recipients = $this->getAlertRecipients($alert);

            if (empty($recipients)) {
                Log::warning('Stock alert created but no recipients found', [
                    'alert_id' => $alert->id,
                    'alert_type' => $alert->type,
                ]);

                return;
            }

            switch ($alert->type) {
                case 'low_stock':
                case 'critical_stock':
                    $this->sendLowStockAlert($alert, $recipients);
                    break;

                case 'near_expiry':
                    $this->sendExpiryAlert($alert, $recipients, 'warning');
                    break;

                case 'critical_expiry':
                    $this->sendExpiryAlert($alert, $recipients, 'critical');
                    break;

                case 'expired':
                    $this->sendExpiryAlert($alert, $recipients, 'expired');
                    break;
            }

            // Log the notification
            Log::info('Stock alert notification sent', [
                'alert_id' => $alert->id,
                'alert_type' => $alert->type,
                'recipients_count' => count($recipients),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send stock alert notification', [
                'alert_id' => $alert->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Send low stock alert email.
     */
    private function sendLowStockAlert(StockAlert $alert, array $recipients): void
    {
        $product = $alert->product ?? $alert->stock?->product;

        if (! $product) {
            Log::warning('Cannot send low stock alert - product not found', [
                'alert_id' => $alert->id,
            ]);

            return;
        }

        Mail::to($recipients)
            ->queue(new LowStockAlert($product));
    }

    /**
     * Send expiry alert email.
     */
    private function sendExpiryAlert(StockAlert $alert, array $recipients, string $alertType): void
    {
        $stock = $alert->stock;

        if (! $stock) {
            Log::warning('Cannot send expiry alert - stock not found', [
                'alert_id' => $alert->id,
            ]);

            return;
        }

        Mail::to($recipients)
            ->queue(new ExpiryAlert($stock, $alertType));
    }

    /**
     * Get alert recipients based on company settings.
     */
    private function getAlertRecipients(StockAlert $alert): array
    {
        $company = $alert->company;

        if (! $company) {
            return [];
        }

        $recipients = [];

        // Company owner/admin users
        $adminUsers = $company->users()
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['Owner', 'Admin', 'Company Owner']);
            })
            ->where('is_active', true)
            ->pluck('email')
            ->toArray();

        $recipients = array_merge($recipients, $adminUsers);

        // Additional configured emails from company settings
        if ($company->alert_emails) {
            $additionalEmails = explode(',', $company->alert_emails);
            $recipients = array_merge($recipients, array_map('trim', $additionalEmails));
        }

        return array_unique(array_filter($recipients));
    }

    /**
     * Handle the StockAlert "updated" event.
     */
    public function updated(StockAlert $alert): void
    {
        // If alert is resolved, we could send a resolution notification
        if ($alert->isDirty('is_resolved') && $alert->is_resolved) {
            Log::info('Stock alert resolved', [
                'alert_id' => $alert->id,
                'resolved_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Handle the StockAlert "deleted" event.
     */
    public function deleted(StockAlert $alert): void
    {
        // Optional: Log deletion
        Log::info('Stock alert deleted', [
            'alert_id' => $alert->id,
            'deleted_by' => auth()->id(),
        ]);
    }

    /**
     * Handle the StockAlert "restored" event.
     */
    public function restored(StockAlert $alert): void
    {
        //
    }

    /**
     * Handle the StockAlert "force deleted" event.
     */
    public function forceDeleted(StockAlert $alert): void
    {
        //
    }
}
