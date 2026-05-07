@extends('layouts.app')

@section('title', 'Ana Sayfa - Denti')
@section('page-title', auth()->user()->name)
@section('page-subtitle', 'Yönetim Paneli')

@section('content')
    <div class="card card-flush mb-8 app-panel-surface border-0 shadow-none bg-transparent">
        <div class="card-body p-0">
            <div class="d-flex flex-column">
                <h1 class="text-gray-900 fw-bolder fs-2tx mb-2">Hoş Geldiniz, {{ $stats['company_name'] ?? 'Denti' }}</h1>
                <p class="text-gray-500 fs-4 fw-semibold">Denti Klinik Yönetim Paneli ile her şey kontrolünüz altında.</p>
            </div>
        </div>
    </div>

    <div class="mb-8">
        <h3 class="fw-bold text-gray-800 mb-6">Genel İstatistikler</h3>
        <div class="row g-5 g-xl-8">
            <!-- Toplam Tedarikçi -->
            <div class="col-md-6 col-xl-3">
                <div class="card card-flush h-md-100 bg-white shadow-sm border-0 rounded-4">
                    <div class="card-header pt-5">
                        <div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-primary me-2 lh-1 ls-n2">{{ $stats['total_suppliers'] ?? 0 }}</span>
                            <span class="text-gray-500 pt-1 fw-semibold fs-6">Toplam Tedarikçi</span>
                        </div>
                    </div>
                    <div class="card-body d-flex align-items-end pt-0">
                        <span class="text-gray-400 fs-7">Sistemdeki kayıtlı tedarikçiler</span>
                    </div>
                </div>
            </div>

            <!-- Toplam Çalışan -->
            <div class="col-md-6 col-xl-3">
                <div class="card card-flush h-md-100 bg-white shadow-sm border-0 rounded-4">
                    <div class="card-header pt-5">
                        <div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-success me-2 lh-1 ls-n2">{{ $stats['total_employees'] ?? 0 }}</span>
                            <span class="text-gray-500 pt-1 fw-semibold fs-6">Toplam Çalışan</span>
                        </div>
                    </div>
                    <div class="card-body d-flex align-items-end pt-0">
                        <span class="text-gray-400 fs-7">Aktif personel sayısı</span>
                    </div>
                </div>
            </div>

            <!-- Toplam Klinik -->
            <div class="col-md-6 col-xl-3">
                <div class="card card-flush h-md-100 bg-white shadow-sm border-0 rounded-4">
                    <div class="card-header pt-5">
                        <div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-warning me-2 lh-1 ls-n2">{{ $stats['total_clinics'] ?? 0 }}</span>
                            <span class="text-gray-500 pt-1 fw-semibold fs-6">Toplam Klinik</span>
                        </div>
                    </div>
                    <div class="card-body d-flex align-items-end pt-0">
                        <span class="text-gray-400 fs-7">Yönetilen klinik sayısı</span>
                    </div>
                </div>
            </div>

            <!-- Stok Kalemi -->
            <div class="col-md-6 col-xl-3">
                <div class="card card-flush h-md-100 bg-white shadow-sm border-0 rounded-4">
                    <div class="card-header pt-5">
                        <div class="card-title d-flex flex-column">
                            <span class="fs-2hx fw-bold text-info me-2 lh-1 ls-n2">{{ $stats['total_stock_items'] ?? 0 }}</span>
                            <span class="text-gray-500 pt-1 fw-semibold fs-6">Stok Kalemi</span>
                        </div>
                    </div>
                    <div class="card-body d-flex align-items-end pt-0">
                        <span class="text-gray-400 fs-7">Katalogdaki ürün çeşidi</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-flush app-panel-surface bg-primary bg-opacity-10 border-0 rounded-4">
        <div class="card-body p-10 text-center">
            <h2 class="text-primary fw-bold mb-4">Mutlu Gülüşler, Profesyonel Yönetim</h2>
            <p class="text-gray-700 fs-5 max-w-800px mx-auto mb-0">
                {{ $stats['company_name'] ?? 'Denti' }} bünyesindeki tüm süreçlerinizi dijitalleştirerek hastalarınıza en iyi hizmeti sunmaya odaklanın.
            </p>
        </div>
    </div>
@endsection
