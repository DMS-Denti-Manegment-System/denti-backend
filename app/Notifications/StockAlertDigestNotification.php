<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StockAlertDigestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected array $items) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Günlük Stok Özeti: Kritik Seviyedeki Ürünler')
            ->greeting('Merhaba '.$notifiable->name.',')
            ->line('Aşağıdaki ürünler kritik stok seviyesinin altına düşmüştür:')
            ->divider();

        $maxItems = 100;
        $itemsToShow = array_slice($this->items, 0, $maxItems);
        $remainingCount = count($this->items) - $maxItems;

        foreach ($itemsToShow as $item) {
            $stock = $item['stock'];
            $alerts = $item['alerts'];

            foreach ($alerts as $alert) {
                $message->line("**{$stock->product->name}**: ".$alert['message']);
            }
        }

        if ($remainingCount > 0) {
            $message->divider()
                ->line("...ve uyarı eşiğindeki diğer {$remainingCount} adet ürün daha mevcut.");
        }

        return $message
            ->action('Stok Yönetimine Git', url('/stocks'))
            ->line('İyi çalışmalar dileriz.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Toplu Stok Uyarısı',
            'message' => count($this->items).' ürün için kritik stok uyarısı mevcut.',
            'items_count' => count($this->items),
            'type' => 'stock_digest',
        ];
    }
}
