<?php

namespace App\Mail;

use App\Models\Stock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExpiryAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Stock $stock;

    public int $daysToExpiry;

    public string $alertType;

    public function __construct(Stock $stock, string $alertType = 'near')
    {
        $this->stock = $stock;
        $this->alertType = $alertType;
        $this->daysToExpiry = $stock->days_to_expiry ?? 0;
    }

    public function envelope(): Envelope
    {
        $subject = match ($this->alertType) {
            'expired' => "🚨 SON KULLANMA TARİHİ GEÇTİ: {$this->stock->product->name}",
            'critical' => "⏰ ACİL - Son kullanma yaklaşıyor: {$this->stock->product->name}",
            default => "⚠️ Son kullanma tarihi yaklaşıyor: {$this->stock->product->name}",
        };

        return new Envelope(
            subject: $subject,
            tags: ['stock-alert', 'expiry'],
            metadata: [
                'stock_id' => $this->stock->id,
                'product_id' => $this->stock->product_id,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alerts.expiry',
            with: [
                'productName' => $this->stock->product->name,
                'productSku' => $this->stock->product->sku,
                'batchCode' => $this->stock->batch_code,
                'expiryDate' => $this->stock->expiry_date->format('d.m.Y'),
                'daysToExpiry' => $this->daysToExpiry,
                'currentStock' => $this->stock->current_stock,
                'unit' => $this->stock->product->unit,
                'alertType' => $this->alertType,
                'stockUrl' => url("/stock/products/{$this->stock->product_id}"),
                'companyName' => config('app.name', 'Klinik'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
