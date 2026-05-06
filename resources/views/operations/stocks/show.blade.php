@extends('layouts.metronic')

@section('title', $product->name . ' - Ürün Detayı')
@section('page-title', 'Ürün Detayı')
@section('page-subtitle', $product->name)

@section('content')
<div class="d-flex flex-column gap-7 gap-lg-10">
    <!-- Action Toolbar -->
    <div class="d-flex justify-content-between align-items-center">
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
    <div class="d-flex flex-column flex-xl-row gap-7 gap-lg-10">
        <!-- Overview -->
        <div class="flex-column flex-lg-row-auto w-100 w-xl-350px">
            <div class="card mb-5 mb-xl-8">
                <div class="card-body pt-15">
                    <div class="d-flex flex-center flex-column mb-5">
                        <div class="symbol symbol-100px symbol-circle mb-7 bg-light-primary">
                            <span class="symbol-label text-primary fw-bold fs-1">
                                <i class="ki-duotone ki-package fs-3x">
                                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                </i>
                            </span>
                        </div>
                        <a href="#" class="fs-3 text-gray-800 text-hover-primary fw-bold mb-1">{{ $product->name }}</a>
                        <div class="fs-5 fw-semibold text-muted mb-6">{{ $product->category ?: 'Kategori Yok' }}</div>
                        
                        <div class="d-flex flex-wrap flex-center">
                            <div class="border border-gray-300 border-dashed rounded py-3 px-3 mb-3">
                                <div class="fs-4 fw-bold text-gray-700">
                                    <span class="w-75px">{{ $product->total_stock }}</span>
                                </div>
                                <div class="fw-semibold text-muted">{{ $product->unit }}</div>
                            </div>
                            <div class="border border-gray-300 border-dashed rounded py-3 px-3 mx-4 mb-3">
                                <div class="fs-4 fw-bold text-gray-700">
                                    <span class="w-75px">{{ number_format($stockStats['total_value'], 2) }}</span>
                                </div>
                                <div class="fw-semibold text-muted">TL Değer</div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-stack fs-4 py-3">
                        <div class="fw-bold rotate collapsible" data-bs-toggle="collapse" href="#kt_product_view_details" role="button" aria-expanded="false" aria-controls="kt_product_view_details">Detaylar 
                        <span class="ms-2 rotate-180">
                            <i class="ki-duotone ki-down fs-3"></i>
                        </span></div>
                    </div>
                    <div class="separator separator-dashed my-3"></div>
                    <div id="kt_product_view_details" class="collapse show">
                        <div class="pb-5 fs-6">
                            <div class="fw-bold mt-5">SKU</div>
                            <div class="text-gray-600">{{ $product->sku ?: '-' }}</div>
                            
                            <div class="fw-bold mt-5">Marka</div>
                            <div class="text-gray-600">{{ $product->brand ?: '-' }}</div>
                            
                            <div class="fw-bold mt-5">Klinik</div>
                            <div class="text-gray-600">{{ $product->clinic?->name ?: 'Genel' }}</div>

                            <div class="fw-bold mt-5">Kritik Seviye (Sarı/Kırmızı)</div>
                            <div class="text-gray-600">
                                <span class="badge badge-light-warning">{{ $product->yellow_alert_level ?? 10 }}</span>
                                <span class="badge badge-light-danger">{{ $product->red_alert_level ?? 5 }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs Content -->
        <div class="flex-lg-row-fluid">
            <ul class="nav nav-custom nav-tabs nav-line-tabs nav-line-tabs-2x border-0 fs-4 fw-semibold mb-8">
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4 active" data-bs-toggle="tab" href="#kt_product_view_batches_tab">Stok Partileri</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_product_view_overview_tab">Genel Bakış</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-active-primary pb-4" data-bs-toggle="tab" href="#kt_product_view_history_tab">İşlem Geçmişi</a>
                </li>
            </ul>

            <div class="tab-content" id="myTabContent">
                <!-- Batches Tab -->
                <div class="tab-pane fade show active" id="kt_product_view_batches_tab" role="tabpanel">
                    <div class="card card-flush mb-6 mb-xl-9">
                        <div class="card-header mt-6">
                            <div class="card-title flex-column">
                                <h2 class="mb-1">Stok Partileri</h2>
                                <div class="fs-6 fw-semibold text-muted">Aktif ve tükenmiş tüm partiler</div>
                            </div>
                        </div>
                        <div class="card-body p-9 pt-4">
                            <div class="table-responsive">
                                <table class="table align-middle table-row-dashed fs-6 gy-5">
                                    <thead>
                                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                            <th>Parti No / SKU</th>
                                            <th>Tedarikçi</th>
                                            <th>Stok</th>
                                            <th>Maliyet</th>
                                            <th>Miyat (SKT)</th>
                                            <th>Konum</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-600 fw-semibold">
                                        @forelse($product->batches as $batch)
                                        <tr>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="text-gray-800 fw-bold">#{{ $batch->id }}</span>
                                                    <span class="fs-8 text-muted">{{ $batch->batch_number ?: 'Parti No Yok' }}</span>
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
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-10 text-muted">Bu ürüne ait stok partisi bulunamadı.</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Overview Tab -->
                <div class="tab-pane fade" id="kt_product_view_overview_tab" role="tabpanel">
                    <div class="card card-flush mb-6 mb-xl-9">
                        <div class="card-header mt-6">
                            <div class="card-title flex-column">
                                <h2 class="mb-1">Stok Özeti</h2>
                                <div class="fs-6 fw-semibold text-muted">Ürünün güncel durumu ve kullanımı</div>
                            </div>
                        </div>
                        <div class="card-body p-9 pt-4">
                             <div class="row g-5">
                                <div class="col-md-4">
                                    <div class="bg-light-success rounded p-6">
                                        <div class="fs-2tx fw-bold text-success mb-2">{{ $stockStats['total_usage'] }}</div>
                                        <div class="fs-6 fw-semibold text-gray-600">Toplam Kullanım</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="bg-light-info rounded p-6">
                                        <div class="fs-2tx fw-bold text-info mb-2">{{ $stockStats['batch_count'] }}</div>
                                        <div class="fs-6 fw-semibold text-gray-600">Aktif Parti Sayısı</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="bg-light-primary rounded p-6">
                                        <div class="fs-2tx fw-bold text-primary mb-2">{{ $product->total_stock }}</div>
                                        <div class="fs-6 fw-semibold text-gray-600">Mevcut Stok</div>
                                    </div>
                                </div>
                             </div>

                             <div class="mt-10">
                                <h3 class="mb-5">Ürün Açıklaması</h3>
                                <div class="fs-5 text-gray-800">
                                    {{ $product->description ?: 'Açıklama bulunmuyor.' }}
                                </div>
                             </div>
                        </div>
                    </div>
                </div>

                <!-- History Tab -->
                <div class="tab-pane fade" id="kt_product_view_history_tab" role="tabpanel">
                    <div class="card card-flush mb-6 mb-xl-9">
                        <div class="card-header mt-6">
                            <div class="card-title flex-column">
                                <h2 class="mb-1">İşlem Geçmişi</h2>
                                <div class="fs-6 fw-semibold text-muted">Son 10 stok hareketi</div>
                            </div>
                        </div>
                        <div class="card-body p-9 pt-4">
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
    </div>
</div>
@endsection

@push('modals')
    <!-- Modal Container -->
    <div class="modal fade" id="stockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-650px" id="stockModalContent">
            <!-- AJAX content -->
        </div>
    </div>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        window.DentiUI?.init(document);
        var $modalElement = $('#stockModal');
        var $modalContent = $('#stockModalContent');
        var modalInstance = new bootstrap.Modal(document.getElementById('stockModal'));
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
    });
</script>
@endpush
