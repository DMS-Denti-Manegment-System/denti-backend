<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Son Kullanma Tarihi Uyarısı</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #faad14;
            margin-bottom: 20px;
        }
        .header.expired {
            border-bottom-color: #ff4d4f;
        }
        .header.critical {
            border-bottom-color: #ff4d4f;
        }
        .alert-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .title {
            color: #faad14;
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .title.expired {
            color: #ff4d4f;
        }
        .product-info {
            background: #fffbe6;
            border-left: 4px solid #faad14;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .product-info.expired {
            background: #fff2f0;
            border-left-color: #ff4d4f;
        }
        .product-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .batch-info {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        .expiry-box {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        .expiry-item {
            text-align: center;
        }
        .expiry-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .expiry-value {
            font-size: 20px;
            font-weight: bold;
        }
        .expired {
            color: #ff4d4f;
        }
        .critical {
            color: #ff4d4f;
        }
        .warning {
            color: #faad14;
        }
        .cta-button {
            display: inline-block;
            background: #1890ff;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 4px;
            margin: 20px 0;
            font-weight: bold;
        }
        .cta-button.danger {
            background: #ff4d4f;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e8e8e8;
            font-size: 12px;
            color: #999;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-expired {
            background: #ff4d4f;
            color: white;
        }
        .badge-critical {
            background: #ff4d4f;
            color: white;
        }
        .badge-warning {
            background: #faad14;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header {{ $alertType }}">
            <div class="alert-icon">
                @if($alertType === 'expired')
                    🚨
                @elseif($alertType === 'critical')
                    ⏰
                @else
                    ⚠️
                @endif
            </div>
            <h1 class="title {{ $alertType }}">
                @if($alertType === 'expired')
                    SON KULLANMA TARİHİ GEÇTİ
                @elseif($alertType === 'critical')
                    ACİL - Son Kullanma Yaklaşıyor
                @else
                    Son Kullanma Tarihi Yaklaşıyor
                @endif
            </h1>
        </div>

        <p>Sayın {{ $clinicName }} Yetkilisi,</p>
        
        @if($alertType === 'expired')
            <p><strong style="color: #ff4d4f;">Aşağıdaki ürünün son kullanma tarihi geçmiştir!</strong></p>
        @elseif($alertType === 'critical')
            <p><strong style="color: #ff4d4f;">Aşağıdaki ürünün son kullanma tarihi çok yaklaşmıştır!</strong></p>
        @else
            <p>Aşağıdaki ürünün son kullanma tarihi yaklaşıyor:</p>
        @endif

        <div class="product-info {{ $alertType }}">
            <div class="product-name">{{ $productName }}</div>
            <div class="batch-info">
                SKU: {{ $productSku }} | Parti: {{ $batchCode }}
            </div>
            <span class="badge badge-{{ $alertType }}">
                @if($alertType === 'expired')
                    TARİHİ GEÇTİ
                @elseif($alertType === 'critical')
                    ACİL
                @else
                    UYARI
                @endif
            </span>
        </div>

        <div class="expiry-box">
            <div class="expiry-item">
                <div class="expiry-label">Son Kullanma</div>
                <div class="expiry-value {{ $alertType }}">{{ $expiryDate }}</div>
            </div>
            <div class="expiry-item">
                <div class="expiry-label">Kalan Süre</div>
                <div class="expiry-value {{ $alertType }}">
                    @if($alertType === 'expired')
                        {{ abs($daysToExpiry) }} gün önce
                    @else
                        {{ $daysToExpiry }} gün
                    @endif
                </div>
            </div>
            <div class="expiry-item">
                <div class="expiry-label">Mevcut Stok</div>
                <div class="expiry-value">{{ $currentStock }} {{ $unit }}</div>
            </div>
        </div>

        <p style="text-align: center;">
            <a href="{{ $stockUrl }}" class="cta-button {{ $alertType === 'expired' ? 'danger' : '' }}">
                @if($alertType === 'expired')
                    Stok İşlemlerini Görüntüle
                @else
                    Ürünü Görüntüle
                @endif
            </a>
        </p>

        <p><strong>Önerilen Aksiyonlar:</strong></p>
        <ul>
            @if($alertType === 'expired')
                <li><span style="color: #ff4d4f; font-weight: bold;">BU ÜRÜNÜ HEMEN KULLANIMDAN KALDIRIN</span></li>
                <li>Stoktan düşüm yapın (fire/çöp)</li>
                <li>Tedarikçiye iade edin (eğer mümkünse)</li>
                <li>Yeni sipariş verin</li>
            @elseif($alertType === 'critical')
                <li>Stok tüketimini hızlandırın</li>
                <li>Gelecek randevularda bu ürünü önceliklendirin</li>
                <li>Yeni sipariş planlayın</li>
            @else
                <li>Kullanım takvimini gözden geçirin</li>
                <li>Gerekirse stok takviyesi yapın</li>
                <li>Tedarikçinizle iletişime geçin</li>
            @endif
        </ul>

        @if($alertType === 'expired')
            <div style="background: #fff2f0; padding: 15px; border-radius: 4px; margin: 20px 0;">
                <strong style="color: #ff4d4f;">⚠️ Önemli:</strong> 
                Son kullanma tarihi geçmiş ürünler hasta güvenliği açısından kullanılmamalıdır.
            </div>
        @endif

        <div class="footer">
            <p>Bu e-posta {{ $clinicName }} stok yönetim sistemi tarafından otomatik gönderilmiştir.</p>
            <p>© {{ date('Y') }} Denti Stok Yönetimi</p>
        </div>
    </div>
</body>
</html>
