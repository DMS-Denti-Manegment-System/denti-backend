<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Düşük Stok Uyarısı</title>
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
            border-bottom: 2px solid #ff4d4f;
            margin-bottom: 20px;
        }
        .alert-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .title {
            color: #ff4d4f;
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        .product-info {
            background: #fff2f0;
            border-left: 4px solid #ff4d4f;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .product-name {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .sku {
            color: #666;
            font-size: 14px;
        }
        .stock-status {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 4px;
        }
        .status-item {
            text-align: center;
        }
        .status-label {
            font-size: 12px;
            color: #666;
            text-transform: uppercase;
        }
        .status-value {
            font-size: 24px;
            font-weight: bold;
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
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e8e8e8;
            font-size: 12px;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="alert-icon">⚠️</div>
            <h1 class="title">Düşük Stok Uyarısı</h1>
        </div>

        <p>Sayın {{ $clinicName }} Yetkilisi,</p>
        
        <p>Aşağıdaki ürünün stoğu kritik seviyenin altına düşmüştür:</p>

        <div class="product-info">
            <div class="product-name">{{ $productName }}</div>
            <div class="sku">SKU: {{ $productSku }}</div>
        </div>

        <div class="stock-status">
            <div class="status-item">
                <div class="status-label">Mevcut Stok</div>
                <div class="status-value {{ $currentStock <= $threshold / 2 ? 'critical' : 'warning' }}">
                    {{ $currentStock }}
                </div>
            </div>
            <div class="status-item">
                <div class="status-label">Eşik Değer</div>
                <div class="status-value warning">{{ $threshold }}</div>
            </div>
            <div class="status-item">
                <div class="status-label">Birim</div>
                <div class="status-value">{{ $unit }}</div>
            </div>
        </div>

        <p style="text-align: center;">
            <a href="{{ $productUrl }}" class="cta-button">Ürünü Görüntüle</a>
        </p>

        <p><strong>Önerilen Aksiyonlar:</strong></p>
        <ul>
            <li>Stok takviyesi yapın</li>
            <li>Tedarikçinizle iletişime geçin</li>
            <li>Kullanım hızını gözden geçirin</li>
        </ul>

        <div class="footer">
            <p>Bu e-posta {{ $clinicName }} stok yönetim sistemi tarafından otomatik gönderilmiştir.</p>
            <p>© {{ date('Y') }} Denti Stok Yönetimi</p>
        </div>
    </div>
</body>
</html>
