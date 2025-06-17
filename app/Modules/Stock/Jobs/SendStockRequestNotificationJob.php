<?php

namespace App\Modules\Stock\Jobs;

use App\Modules\Stock\Models\StockRequest;
use App\Modules\Stock\Notifications\StockRequestNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class SendStockRequestNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $stockRequest;

    public function __construct(StockRequest $stockRequest)
    {
        $this->stockRequest = $stockRequest;
    }

    public function handle()
    {
        // Burada gerçek kullanıcı bilgilerini alıp bildirim gönderebilirsiniz
        // Örnek: Hedef klinik sorumlusuna bildirim

        // $users = User::where('clinic_id', $this->stockRequest->requested_from_clinic_id)->get();
        // Notification::send($users, new StockRequestNotification($this->stockRequest));

        // Log için
        \Log::info('Stock request notification sent', [
            'request_id' => $this->stockRequest->id,
            'request_number' => $this->stockRequest->request_number
        ]);
    }
}