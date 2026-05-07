<?php

namespace App\Notifications;

use App\Models\StockAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockLowLevelNotification extends Notification
{
    use Queueable;

    protected $alert;

    public function __construct(StockAlert $alert)
    {
        $this->alert = $alert;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $color = $this->alert->type === 'critical_stock' ? 'red' : 'orange';

        return (new MailMessage)
            ->subject($this->alert->title)
            ->greeting('Stok Uyarısı!')
            ->line($this->alert->message)
            ->line("Klinik: {$this->alert->clinic->name}")
            ->line("Mevcut Seviye: {$this->alert->current_stock_level}")
            ->line("Eşik Seviye: {$this->alert->threshold_level}")
            ->action('Stoku Görüntüle', url('/stocks/'.$this->alert->stock_id))
            ->line('Lütfen gerekli aksiyonu alın.');
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'stock_alert',
            'alert_id' => $this->alert->id,
            'alert_type' => $this->alert->type,
            'stock_name' => $this->alert->stock->name,
            'clinic_name' => $this->alert->clinic->name,
            'message' => $this->alert->message,
        ];
    }
}
