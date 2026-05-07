<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Product $product;

    public int $currentStock;

    public int $threshold;

    public function __construct(Product $product)
    {
        $this->product = $product;
        $this->currentStock = $product->total_stock;
        $this->threshold = $product->min_stock_level;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "⚠️ Düşük Stok Uyarısı: {$this->product->name}",
            tags: ['stock-alert', 'low-stock'],
            metadata: [
                'product_id' => $this->product->id,
                'company_id' => $this->product->company_id,
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.alerts.low-stock',
            with: [
                'productName' => $this->product->name,
                'productSku' => $this->product->sku,
                'currentStock' => $this->currentStock,
                'threshold' => $this->threshold,
                'unit' => $this->product->unit,
                'productUrl' => url("/stock/products/{$this->product->id}"),
                'companyName' => $this->product->company->name ?? 'Klinik',
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
