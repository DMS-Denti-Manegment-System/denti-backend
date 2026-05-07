@extends('layouts.app')

@section('title', $product->name . ' - Ürün Detayı')
@section('page-title', 'Ürün Detayı')
@section('page-subtitle', $product->name)

@section('content')
<div class="d-flex flex-column gap-7 gap-lg-10">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <a href="{{ route('stocks.index') }}" class="btn btn-sm btn-light-primary">
            <i class="ki-duotone ki-arrow-left fs-3"><span class="path1"></span><span class="path2"></span></i>
            Listeye Dön
        </a>
        <div class="d-flex gap-3">
            <button type="button" class="btn btn-sm btn-light-info" data-stock-adjust="{{ $product->id }}">
                <i class="ki-duotone ki-plus-square fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                Stok Ekle/Çıkar
            </button>
            <button type="button" class="btn btn-sm btn-primary" data-stock-edit="{{ $product->id }}">
                <i class="ki-duotone ki-pencil fs-3"><span class="path1"></span><span class="path2"></span></i>
                Düzenle
            </button>
        </div>
    </div>

    @if($errors->has('stock_use'))
        <div class="alert alert-danger">{{ $errors->first('stock_use') }}</div>
    @endif

    <div class="row g-7">
        <div class="col-xl-4">
            <div class="card card-flush app-stock-detail-card h-100">
                <div class="card-body p-8">
                    <div class="d-flex align-items-center gap-4 mb-8">
                        <div class="symbol symbol-80px symbol-circle bg-light-primary">
                            <span class="symbol-label text-primary fw-bold fs-1">
                                <i class="ki-duotone ki-package fs-2x">
                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                </i>
                            </span>
                        </div>
                        <div>
                            <div class="fs-2 fw-bold text-gray-900">{{ $product->name }}</div>
                            <div class="text-muted">{{ $product->category ?: 'Kategori Yok' }}</div>
                        </div>
                    </div>

                    <div class="row g-4 mb-8">
                        <div class="col-6">
                            <div class="border border-gray-300 border-dashed rounded px-4 py-4">
                                <div class="text-muted fs-7 mb-1">Mevcut Stok</div>
                                <div class="fs-3 fw-bold text-gray-900">{{ $product->total_stock }} {{ $product->unit }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border border-gray-300 border-dashed rounded px-4 py-4">
                                <div class="text-muted fs-7 mb-1">Toplam Değer</div>
                                <div class="fs-3 fw-bold text-gray-900">{{ number_format($stockStats['total_value'], 2) }} TL</div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-8">
                        <div class="fw-bold text-gray-900 fs-5 mb-4">Ürün Bilgileri</div>
                        <div class="d-flex flex-column gap-4">
                            <div>
                                <div class="text-muted fs-7">SKU</div>
                                <div class="text-gray-800 fw-semibold">{{ $product->sku ?: '-' }}</div>
                            </div>
                            <div>
                                <div class="text-muted fs-7">Marka</div>
                                <div class="text-gray-800 fw-semibold">{{ $product->brand ?: '-' }}</div>
                            </div>
                            <div>
                                <div class="text-muted fs-7">Klinik</div>
                                <div class="text-gray-800 fw-semibold">{{ $product->clinic?->name ?: 'Genel' }}</div>
                            </div>
                            <div>
                                <div class="text-muted fs-7">Uyarı Seviyesi</div>
                                <div class="d-flex gap-2">
                                    <span class="badge badge-light-warning">{{ $product->yellow_alert_level ?? 10 }}</span>
                                    <span class="badge badge-light-danger">{{ $product->red_alert_level ?? 5 }}</span>
                                </div>
                            </div>
                            <div>
                                <div class="text-muted fs-7">Açıklama</div>
                                <div class="text-gray-800 fw-semibold">{{ $product->description ?: 'Açıklama bulunmuyor.' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="border-top pt-6">
                        <div class="fw-bold text-gray-900 fs-5 mb-4">Stok İşlemleri</div>
                        <div class="d-flex flex-wrap gap-3">
                            @if(!$hasExpiryTracking)
                                @if($defaultUsageBatch)
                                    <button
                                        type="button"
                                        class="btn btn-danger"
                                        data-stock-use-trigger
                                        data-stock-use-action="{{ route('stocks.use', $defaultUsageBatch) }}"
                                        data-stock-use-batch="#{{ $defaultUsageBatch->id }} / {{ $product->name }}"
                                        data-stock-use-max="{{ $defaultUsageBatch->current_stock }}"
                                        data-stock-use-reason="Ürün Detay Kullanımı"
                                    >
                                        Stok Kullan
                                    </button>
                                @else
                                    <div class="text-muted">Kullanılabilir aktif stok bulunmuyor.</div>
                                @endif
                            @endif

                            @if($hasExpiryTracking)
                                <button type="button" class="btn btn-primary" data-batch-create-trigger>
                                    Yeni Stok Ekle
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-8">
            <div class="card card-flush app-stock-detail-card mb-7">
                <div class="card-header">
                    <div class="card-title">
                        <h2 class="mb-0">Stok Değişim Grafiği (Son 15 Gün)</h2>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div id="stock_movement_chart" style="height: 250px;"></div>
                </div>
            </div>

            <div class="card card-flush app-stock-detail-card mb-7">
                <div class="card-header">
                    <div class="card-title">
                        <h2 class="mb-0">Stok Özeti</h2>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <div class="row g-5">
                        <div class="col-md-4">
                            <div class="bg-light-success rounded p-6 h-100">
                                <div class="fs-2tx fw-bold text-success mb-2">{{ $stockStats['total_usage'] }}</div>
                                <div class="fs-6 fw-semibold text-gray-600">Toplam Kullanım</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-light-info rounded p-6 h-100">
                                <div class="fs-2tx fw-bold text-info mb-2">{{ $stockStats['batch_count'] }}</div>
                                <div class="fs-6 fw-semibold text-gray-600">Aktif Giriş Sayısı</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="bg-light-primary rounded p-6 h-100">
                                <div class="fs-2tx fw-bold text-primary mb-2">{{ $product->total_stock }}</div>
                                <div class="fs-6 fw-semibold text-gray-600">Mevcut Stok</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-flush app-stock-detail-card mb-7">
                <div class="card-header align-items-center">
                    <div class="card-title">
                        <h2 class="mb-0">Stok Girişleri</h2>
                    </div>
                    @if($hasExpiryTracking)
                        <div class="card-toolbar">
                            <button type="button" class="btn btn-sm btn-light-primary" data-batch-create-trigger>
                                Yeni Stok Ekle
                            </button>
                        </div>
                    @endif
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th>Giriş No / SKU</th>
                                    <th>Tedarikçi</th>
                                    <th>Stok</th>
                                    <th>Maliyet</th>
                                    <th>Miyat (SKT)</th>
                                    <th>Konum</th>
                                    @if($hasExpiryTracking)
                                        <th>Kullanım</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 fw-semibold">
                                @forelse($product->batches as $batch)
                                <tr>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span class="text-gray-800 fw-bold">#{{ $batch->id }}</span>
                                            <span class="fs-8 text-muted">{{ $batch->batch_code ?: 'Kayıt Kodu Yok' }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $batch->supplier?->name ?: '-' }}</td>
                                    <td>
                                        <span class="fw-bold {{ $batch->current_stock <= 0 ? 'text-danger' : 'text-gray-800' }}">
                                            {{ $batch->current_stock }} {{ $product->unit }}
                                        </span>
                                    </td>
                                    <td>{{ number_format($batch->purchase_price, 2) }} {{ $batch->currency }}</td>
                                    <td>
                                        @if($batch->expiry_date)
                                            <span class="badge badge-light-{{ $batch->expiry_date->isPast() ? 'danger' : ($batch->expiry_date->diffInDays() < 30 ? 'warning' : 'success') }}">
                                                {{ $batch->expiry_date->format('d/m/Y') }}
                                            </span>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $batch->storage_location ?: '-' }}</td>
                                    @if($hasExpiryTracking)
                                        <td class="min-w-175px">
                                            @if($batch->is_active && $batch->current_stock > 0 && (!$batch->expiry_date || !$batch->expiry_date->isPast()))
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-light-danger"
                                                    data-stock-use-trigger
                                                    data-stock-use-action="{{ route('stocks.use', $batch) }}"
                                                    data-stock-use-batch="#{{ $batch->id }} / {{ $batch->batch_code ?: $product->name }}"
                                                    data-stock-use-max="{{ $batch->current_stock }}"
                                                    data-stock-use-reason="Parti Bazlı Kullanım"
                                                >
                                                    Stok Kullan
                                                </button>
                                            @else
                                                <span class="text-muted fs-8">Kullanılamaz</span>
                                            @endif
                                        </td>
                                    @endif
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ $hasExpiryTracking ? 7 : 6 }}" class="text-center py-10 text-muted">Bu ürüne ait stok partisi bulunamadı.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card card-flush app-stock-detail-card">
                <div class="card-header">
                    <div class="card-title">
                        <h2 class="mb-0">İşlem Geçmişi</h2>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <div class="table-responsive">
                        <table class="table align-middle table-row-dashed fs-6 gy-5">
                            <thead>
                                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                    <th class="min-w-100px">Tarih</th>
                                    <th class="min-w-100px">İşlem</th>
                                    <th class="min-w-100px">Miktar</th>
                                    <th class="min-w-100px">Kullanıcı</th>
                                    <th class="min-w-150px">Notlar</th>
                                </tr>
                            </thead>
                            <tbody class="text-gray-600 fw-semibold">
                                @forelse($transactions as $txn)
                                <tr>
                                    <td>{{ $txn->transaction_date->format('d/m/Y H:i') }}</td>
                                    <td>
                                        @php
                                            $typeLabels = [
                                                'purchase' => ['success', 'Alım'],
                                                'usage' => ['info', 'Kullanım'],
                                                'adjustment_increase' => ['primary', 'Düzeltme (+)'],
                                                'adjustment_decrease' => ['danger', 'Düzeltme (-)'],
                                                'transfer_in' => ['success', 'Transfer (Gelen)'],
                                                'transfer_out' => ['warning', 'Transfer (Giden)'],
                                            ];
                                            $label = $typeLabels[$txn->type] ?? ['secondary', $txn->type];
                                        @endphp
                                        <span class="badge badge-light-{{ $label[0] }}">{{ $label[1] }}</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold {{ $txn->quantity > 0 ? 'text-success' : 'text-danger' }}">
                                            {{ $txn->quantity > 0 ? '+' : '' }}{{ $txn->quantity }}
                                        </span>
                                    </td>
                                    <td>{{ $txn->user?->name ?: ($txn->performed_by ?: 'Sistem') }}</td>
                                    <td>{{ $txn->notes ?: ($txn->description ?: '-') }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-10 text-muted">İşlem geçmişi bulunamadı.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-5">
                        {{ $transactions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')
    <div class="modal fade" id="stockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px" id="stockModalContent">
        </div>
    </div>

    <div class="modal fade" id="stockUseModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-550px">
            <div class="modal-content">
                <form method="POST" id="stockUseForm" class="modal-form">
                    @csrf
                    <div class="modal-header">
                        <div>
                            <h2 class="fw-bold mb-1">Stok Kullan</h2>
                            <div class="text-muted fs-7" id="stockUseModalBatch">Seçilen stok</div>
                        </div>
                        <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                            <i class="ki-duotone ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body py-7 px-10">
                        <div class="alert alert-light-info d-flex align-items-center gap-3 mb-6">
                            <i class="ki-duotone ki-information-4 fs-2 text-info">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <div class="text-gray-700 fs-7">
                                Kullanilabilir stok: <span class="fw-bold" id="stockUseModalMax">0</span>
                            </div>
                        </div>
                        <div class="row g-5">
                            <div class="col-md-5">
                                <label class="form-label required">Miktar</label>
                                <input type="number" min="1" name="quantity" id="stockUseQuantity" class="form-control form-control-solid" required>
                            </div>
                            <div class="col-md-7">
                                <label class="form-label">Çıkış Sebebi</label>
                                <select name="reason" id="stockUseReason" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#stockUseModal" data-hide-search="true">
                                    <option value="Kullanım">Kullanım</option>
                                    <option value="Transfer">Transfer</option>
                                    <option value="Değişim">Değişim</option>
                                    <option value="İade">İade</option>
                                    <option value="Süresi geçmiş">Süresi geçmiş</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Not</label>
                                <textarea name="notes" rows="3" class="form-control form-control-solid" placeholder="Opsiyonel not"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Kapat</button>
                        <button type="submit" class="btn btn-danger">Stok Kullan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if($hasExpiryTracking)
        <div class="modal fade" id="stockBatchCreateModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered mw-750px">
                <div class="modal-content">
                    <form method="POST" action="{{ route('stocks.batches.store', $product) }}" class="modal-form">
                        @csrf
                        <div class="modal-header">
                            <div>
                                <h2 class="fw-bold mb-1">Yeni Ürün Girişi</h2>
                                <div class="text-muted fs-7">{{ $product->name }} için yeni stok girişi yapın.</div>
                            </div>
                            <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Close">
                                <i class="ki-duotone ki-cross fs-1"></i>
                            </button>
                        </div>
                        <div class="modal-body py-7 px-10">
                            <div class="row g-5">
                                <!-- Tedarikçi & Klinik -->
                                <div class="col-md-6">
                                    <label class="form-label required">Tedarikçi</label>
                                    <select name="supplier_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#stockBatchCreateModal" required>
                                        <option value="">Tedarikçi Seçin</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Klinik</label>
                                    <select name="clinic_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#stockBatchCreateModal" required>
                                        @foreach($clinics as $clinic)
                                            <option value="{{ $clinic->id }}" @selected(old('clinic_id', $product->clinic_id) == $clinic->id)>{{ $clinic->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Miktar & Fiyat -->
                                <div class="col-md-4">
                                    <label class="form-label required">Miktar</label>
                                    <input type="number" min="1" name="quantity" value="{{ old('quantity') }}" class="form-control form-control-solid" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Birim Fiyat</label>
                                    <input type="number" step="0.01" min="0" name="purchase_price" value="{{ old('purchase_price') }}" class="form-control form-control-solid">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Para Birimi</label>
                                    <select name="currency" class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-dropdown-parent="#stockBatchCreateModal">
                                        @foreach($currencies as $code => $label)
                                            <option value="{{ $code }}" @selected(old('currency', 'TRY') === $code)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Tarihler -->
                                <div class="col-md-6">
                                    <label class="form-label">Alış Tarihi</label>
                                    <input type="date" name="purchase_date" value="{{ old('purchase_date', now()->format('Y-m-d')) }}" class="form-control form-control-solid">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">S.K.T</label>
                                    <input type="date" name="expiry_date" value="{{ old('expiry_date') }}" class="form-control form-control-solid" required>
                                </div>

                                <!-- Alarmlar -->
                                <div class="col-md-6">
                                    <label class="form-label">SKT Sarı Alarm (Gün)</label>
                                    <input type="number" min="0" name="expiry_yellow_days" value="{{ old('expiry_yellow_days', 30) }}" class="form-control form-control-solid">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">SKT Kritik Alarm (Gün)</label>
                                    <input type="number" min="0" name="expiry_red_days" value="{{ old('expiry_red_days', 10) }}" class="form-control form-control-solid">
                                </div>

                                <!-- Konum -->
                                <div class="col-12">
                                    <label class="form-label">Depolama Konumu</label>
                                    <input type="text" name="storage_location" value="{{ old('storage_location') }}" class="form-control form-control-solid" placeholder="Raf / Bölme">
                                </div>

                                <!-- Birim Ayarları -->
                                <div class="col-12 mt-5">
                                    <div class="separator separator-dashed my-5"></div>
                                    <h4 class="fw-bold mb-5">Birim & Alt Birim Ayarları</h4>
                                    <div class="row g-5">
                                        <div class="col-md-6">
                                            <label class="required form-label">Ana Birim</label>
                                            <select name="unit" class="form-select form-select-solid" data-control="select2" data-hide-search="true" data-dropdown-parent="#stockBatchCreateModal">
                                                @foreach($units as $u)
                                                    <option value="{{ $u }}" @selected(old('unit', $product->unit) === $u)>{{ $u }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-check form-switch form-check-custom form-check-solid mt-9">
                                                <input type="hidden" name="has_sub_unit" value="0" />
                                                <input class="form-check-input" type="checkbox" name="has_sub_unit" value="1" id="sub_unit_toggle_batch" @checked(old('has_sub_unit', $product->has_sub_unit)) />
                                                <span class="form-check-label fw-bold text-gray-700">Alt Birim Kullan</span>
                                            </label>
                                        </div>
                                        <div id="sub_unit_container_batch" class="{{ old('has_sub_unit', $product->has_sub_unit) ? '' : 'd-none' }} col-12">
                                            <div class="row g-5">
                                                <div class="col-md-6">
                                                    <label class="form-label">Alt Birim Adı</label>
                                                    <input type="text" name="sub_unit_name" class="form-control form-control-solid" value="{{ old('sub_unit_name', $product->sub_unit_name) }}" placeholder="örn: Adet" />
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Çarpan (1 Ana Birim = ? Alt Birim)</label>
                                                    <input type="number" name="sub_unit_multiplier" class="form-control form-control-solid" value="{{ old('sub_unit_multiplier', $product->sub_unit_multiplier) }}" placeholder="örn: 10" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">İptal</button>
                            <button type="submit" class="btn btn-primary">Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        window.DentiUI?.init(document);
        var $modalElement = $('#stockModal');
        var $modalContent = $('#stockModalContent');
        
        // Chart Initialization
        var chartData = @json($chartData);
        var chartElement = document.getElementById('stock_movement_chart');
        if (chartElement && chartData.length > 0) {
            var options = {
                series: [{
                    name: 'Stok Miktarı',
                    data: chartData.map(item => item.value)
                }],
                chart: {
                    fontFamily: 'inherit',
                    type: 'area',
                    height: 250,
                    toolbar: { show: false }
                },
                colors: ['#009ef7'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.3,
                        opacityTo: 0.1,
                        stops: [0, 90, 100]
                    }
                },
                stroke: {
                    curve: 'smooth',
                    show: true,
                    width: 3,
                    colors: ['#009ef7']
                },
                xaxis: {
                    categories: chartData.map(item => {
                        const date = new Date(item.date);
                        return date.toLocaleDateString('tr-TR', { day: '2-digit', month: '2-digit' });
                    }),
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                    labels: {
                        style: { colors: '#a1a5b7', fontSize: '12px' }
                    }
                },
                yaxis: {
                    labels: {
                        style: { colors: '#a1a5b7', fontSize: '12px' }
                    }
                },
                grid: {
                    borderColor: '#f1f1f1',
                    strokeDashArray: 4,
                    yaxis: { lines: { show: true } }
                },
                markers: {
                    size: 4,
                    colors: ['#009ef7'],
                    strokeColors: '#fff',
                    strokeWidth: 2
                },
                tooltip: {
                    style: { fontSize: '12px' },
                    y: {
                        formatter: function (val) {
                            return val + ' {{ $product->unit }}'
                        }
                    }
                }
            };

            var chart = new ApexCharts(chartElement, options);
            chart.render();
        }

        var modalInstance = new bootstrap.Modal(document.getElementById('stockModal'));
        var stockUseModalElement = document.getElementById('stockUseModal');
        var stockUseModal = stockUseModalElement ? new bootstrap.Modal(stockUseModalElement) : null;
        var stockBatchCreateModalElement = document.getElementById('stockBatchCreateModal');
        var stockBatchCreateModal = stockBatchCreateModalElement ? new bootstrap.Modal(stockBatchCreateModalElement) : null;
        var stockIndexUrl = "{{ route('stocks.index') }}";

        function openModal(mode, id) {
            modalInstance.hide();
            $('.modal-backdrop').remove();
            
            var url = stockIndexUrl + (stockIndexUrl.indexOf('?') !== -1 ? '&' : '?') + 'modal=' + mode;
            if (id) {
                var param = (mode === 'edit') ? 'edit' : 'product';
                url += '&' + param + '=' + id;
            }

            $modalContent.html('<div class="modal-content"><div class="modal-body text-center py-20"><div class="spinner-border text-primary"></div></div></div>');
            modalInstance.show();

            $.ajax({
                url: url,
                type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(response) {
                    $modalContent.html(response.modalHtml);
                    initModalComponents();
                    if (modalInstance.handleUpdate) modalInstance.handleUpdate(); 
                },
                error: function() {
                    modalInstance.hide();
                    window.DentiUI?.notify('error', 'Islem sirasinda bir hata olustu.');
                }
            });
        }

        function initModalComponents() {
            window.DentiUI?.init($modalElement[0]);

            var $expiryToggle = $('#expiry_toggle');
            if ($expiryToggle.length) {
                $expiryToggle.on('change', function() {
                    var isChecked = $(this).is(':checked');
                    $('#expiry_date_container').toggleClass('d-none', !isChecked);
                });
            }

            var $subUnitToggle = $('#sub_unit_toggle');
            if ($subUnitToggle.length) {
                $subUnitToggle.on('change', function() {
                    var isChecked = $(this).is(':checked');
                    $('#sub_unit_container').toggleClass('d-none', !isChecked);
                });
            }
        }


        $(document).on('click', '[data-stock-edit]', function() { openModal('edit', $(this).attr('data-stock-edit')); });
        $(document).on('click', '[data-stock-adjust]', function() { openModal('adjust', $(this).attr('data-stock-adjust')); });
        $(document).on('click', '[data-batch-create-trigger]', function() {
            if (stockBatchCreateModal) {
                stockBatchCreateModal.show();
            }
        });

        $(document).on('change', '#sub_unit_toggle_batch', function() {
            var isChecked = $(this).is(':checked');
            $('#sub_unit_container_batch').toggleClass('d-none', !isChecked);
        });
        $(document).on('click', '[data-stock-use-trigger]', function() {
            if (!stockUseModal) {
                return;
            }

            var $trigger = $(this);
            $('#stockUseForm').attr('action', $trigger.attr('data-stock-use-action'));
            $('#stockUseModalBatch').text($trigger.attr('data-stock-use-batch') || 'Seçilen stok');
            $('#stockUseModalMax').text(($trigger.attr('data-stock-use-max') || '0') + ' {{ $product->unit }}');
            $('#stockUseQuantity')
                .attr('max', $trigger.attr('data-stock-use-max') || '')
                .val('');
            
            // Re-init select2 when modal opens to prevent width/parent bugs
            var $reasonSelect = $('#stockUseReason');
            $reasonSelect.val($trigger.attr('data-stock-use-reason') || 'Kullanım').trigger('change');
            
            if ($.fn.select2) {
                $reasonSelect.select2({
                    dropdownParent: $('#stockUseModal'),
                    minimumResultsForSearch: Infinity
                });
            }

            $('#stockUseForm').find('textarea[name="notes"]').val('');
            stockUseModal.show();
        });

        $(document).on('submit', '#stockForm, #stockAdjustForm', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var method = $form.find('input[name="_method"]').val() || 'POST';

            $btn.attr('data-kt-indicator', 'on').prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                type: method,
                data: $form.serialize(),
                success: function(response) {
                    window.DentiUI?.notify('success', response.message || 'Basarili');
                    setTimeout(function() { window.location.reload(); }, 1000);
                },
                error: function(xhr) {
                    $btn.removeAttr('data-kt-indicator').prop('disabled', false);
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        window.DentiUI?.showValidationErrors(xhr.responseJSON.errors);
                    } else {
                        window.DentiUI?.notify('error', 'Bir hata olustu.');
                    }
                }
            });
        });

        @if($hasExpiryTracking && $errors->hasAny(['supplier_id', 'quantity', 'purchase_price', 'currency', 'purchase_date', 'expiry_date', 'storage_location', 'expiry_yellow_days', 'expiry_red_days', 'batch_code']))
            stockBatchCreateModal?.show();
        @endif
    });
</script>
@endpush
