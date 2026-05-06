@extends('layouts.metronic')

@section('title', 'Dashboard - Denti')
@section('page-title', $stats['company_name'] ?? 'Dashboard')
@section('page-subtitle', 'Operasyon ozet gorunumu')

@section('content')
    @php
        $cards = [
            ['label' => 'Toplam Calisan', 'value' => $stats['total_employees'] ?? 0, 'color' => 'primary'],
            ['label' => 'Toplam Klinik', 'value' => $stats['total_clinics'] ?? 0, 'color' => 'success'],
            ['label' => 'Stok Kalemi', 'value' => $stats['total_stock_items'] ?? 0, 'color' => 'warning'],
            ['label' => 'Toplam Tedarikci', 'value' => $stats['total_suppliers'] ?? 0, 'color' => 'info'],
        ];
        if (!empty($stats['is_super_admin'])) {
            $cards[] = ['label' => 'Sirket Sayisi', 'value' => $stats['total_companies'] ?? 0, 'color' => 'danger'];
        }

        $insights = [
            ['icon' => 'S', 'title' => 'Stok nabzi', 'body' => (($stats['total_stock_items'] ?? 0) > 0 ? $stats['total_stock_items'] : '0') . ' aktif kalem dogrudan merkezi sorgudan geliyor.'],
            ['icon' => 'K', 'title' => 'Klinik yayilimi', 'body' => (($stats['total_clinics'] ?? 0) > 0 ? $stats['total_clinics'] : '0') . ' klinik icin tek shell ve ortak filtre dili aktif.'],
            ['icon' => 'E', 'title' => 'Ekip yogunlugu', 'body' => (($stats['total_employees'] ?? 0) > 0 ? $stats['total_employees'] : '0') . ' kullanici yeni Blade panelden yonetilebilir gorunumde.'],
        ];

        $actions = [
            ['href' => url('/stocks'), 'icon' => 'ST', 'title' => 'Stoklara git', 'body' => 'Kalemleri, detaylari ve mevcut seviyeleri hizli kontrol et.'],
            ['href' => url('/stock-requests'), 'icon' => 'TR', 'title' => 'Talepleri ac', 'body' => 'Klinikler arasi akisi ve bekleyen hareketleri goru.'],
            ['href' => url('/alerts'), 'icon' => 'AL', 'title' => 'Uyarilari izle', 'body' => 'Kritik stok, SKT ve operasyon sinyallerini tek yerden takip et.'],
            ['href' => url('/suppliers'), 'icon' => 'TD', 'title' => 'Tedarik agi', 'body' => 'Tedarikci kartlarini ve temel iletisim bilgilerini yonet.'],
        ];
    @endphp

    <div class="row g-5 g-xl-8 mb-8">
        @foreach ($cards as $card)
            <div class="col-md-6 col-xl-3">
                <div class="card card-flush h-md-100 app-summary-card app-kpi-card bg-{{ $card['color'] }} bg-opacity-10 app-panel-surface">
                    <div class="card-header pt-5">
                        <div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-{{ $card['color'] }} me-2 lh-1 ls-n2">{{ $card['value'] }}</span>
                            <span class="text-gray-500 pt-1 fw-semibold fs-6">{{ $card['label'] }}</span>
                        </div>
                    </div>
                    <div class="card-body d-flex align-items-end pt-0">
                        <span class="text-gray-600 fs-7">Canli backend verisi uzerinden olusan ozet.</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="app-dashboard-grid">
        <div class="card card-flush h-xl-100 app-panel-surface">
            <div class="card-header pt-7">
                <div class="d-flex flex-column">
                    <span class="app-panel-heading">Gunluk Ozet</span>
                    <h3 class="card-title fw-bold text-gray-900 mt-2">Operasyon icgorusleri</h3>
                </div>
            </div>
            <div class="card-body pt-3">
                <div class="app-insight-list">
                    @foreach ($insights as $insight)
                        <div class="app-insight-item">
                            <span class="app-insight-icon">{{ $insight['icon'] }}</span>
                            <div>
                                <div class="fw-bold text-gray-900 mb-1">{{ $insight['title'] }}</div>
                                <div class="text-gray-600 fs-7">{{ $insight['body'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="card card-flush h-xl-100 app-panel-surface">
            <div class="card-header pt-7">
                <div class="d-flex flex-column">
                    <span class="app-panel-heading">Hizli Erisim</span>
                    <h3 class="card-title fw-bold text-gray-900 mt-2">Kritik moduller</h3>
                </div>
            </div>
            <div class="card-body pt-3">
                <div class="app-action-list">
                    @foreach ($actions as $action)
                        <a href="{{ $action['href'] }}" class="app-action-link">
                            <span class="app-action-icon">{{ $action['icon'] }}</span>
                            <div>
                                <div class="fw-bold text-gray-900 mb-1">{{ $action['title'] }}</div>
                                <div class="text-gray-600 fs-7">{{ $action['body'] }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
