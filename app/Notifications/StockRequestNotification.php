<?php

namespace App\Notifications;

use App\Models\StockRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockRequestNotification extends Notification
{
    use Queueable;

    protected $stockRequest;

    public function __construct(StockRequest $stockRequest)
    {
        $this->stockRequest = $stockRequest;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Yeni Stok Talebi')
            ->greeting('Merhaba!')
            ->line('Yeni bir stok talebi aldınız.')
            ->line("Talep Eden: {$this->stockRequest->requesterClinic->name}")
            ->line("Ürün: {$this->stockRequest->stock->name}")
            ->line("Miktar: {$this->stockRequest->requested_quantity} {$this->stockRequest->stock->unit}")
            ->line("Talep Sebebi: {$this->stockRequest->request_reason}")
            ->action('Talebi Görüntüle', url('/stock-requests/'.$this->stockRequest->id))
            ->line('Teşekkürler!');
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'stock_request',
            'request_id' => $this->stockRequest->id,
            'request_number' => $this->stockRequest->request_number,
            'requester_clinic' => $this->stockRequest->requesterClinic->name,
            'stock_name' => $this->stockRequest->stock->name,
            'quantity' => $this->stockRequest->requested_quantity,
            'message' => "Yeni stok talebi: {$this->stockRequest->stock->name} ({$this->stockRequest->requested_quantity} {$this->stockRequest->stock->unit})",
        ];
    }
}
