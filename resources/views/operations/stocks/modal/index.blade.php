@if(in_array($modalMode, ['create', 'edit'], true))
    @include('operations.stocks.modal.form')
@elseif($modalMode === 'detail' && $selectedProduct)
    <div class="modal-content">
        <div class="modal-header border-0 pb-0">
            <div>
                <h2 class="fw-bold mb-1">{{ $selectedProduct->name }}</h2>
                <div class="text-muted fs-7">
                    {{ $selectedProduct->category ?: 'Kategori belirtilmedi' }} |
                    SKU: {{ $selectedProduct->sku ?: 'Belirtilmedi' }}
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-primary" data-stock-adjust="{{ $selectedProduct->id }}">
                    Stok Girişi Yap
                </button>
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
        </div>

        <div class="modal-body py-8 px-lg-17">
            <div class="row g-5 mb-8">
                <div class="col-md-3">
                    <div class="text-muted fs-7">Toplam Stok</div>
                    <div class="fs-2 fw-bold">{{ $selectedProduct->total_stock }} {{ $selectedProduct->unit }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted fs-7">Parti Sayısı</div>
                    <div class="fs-2 fw-bold">{{ $detailMeta['batch_count'] }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted fs-7">Minimum Seviye</div>
                    <div class="fs-2 fw-bold text-warning">{{ $selectedProduct->yellow_alert_level ?? $selectedProduct->min_stock_level ?? 0 }}</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted fs-7">Kritik Seviye</div>
                    <div class="fs-2 fw-bold text-danger">{{ $selectedProduct->red_alert_level ?? $selectedProduct->critical_stock_level ?? 0 }}</div>
                </div>
            </div>

            <ul class="nav nav-line-tabs nav-line-tabs-2x fs-6 fw-semibold mb-6">
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeDetailTab === 'history' ? 'active' : '' }}" data-stock-tab="history" data-stock-product="{{ $selectedProduct->id }}">İşlem Geçmişi</a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeDetailTab === 'analytics' ? 'active' : '' }}" data-stock-tab="analytics" data-stock-product="{{ $selectedProduct->id }}">Grafik / Analiz</a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link {{ $activeDetailTab === 'info' ? 'active' : '' }}" data-stock-tab="info" data-stock-product="{{ $selectedProduct->id }}">Ürün Bilgileri</a>
                </li>
            </ul>

            @if($activeDetailTab === 'history')
                <div class="table-responsive">
                    <table class="table align-middle fs-6 gy-4">
                        <thead>
                            <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                                <th>Tarih</th>
                                <th>İşlem</th>
                                <th>Miktar</th>
                                <th>Yeni Stok</th>
                                <th>İşlemi Yapan</th>
                                <th>Açıklama</th>
                            </tr>
                        </thead>
                        <tbody class="fw-semibold text-gray-700">
                            @forelse($selectedTransactions as $transaction)
                                @php $isPositive = in_array($transaction->type, ['purchase', 'adjustment_increase', 'transfer_in', 'return_in'], true); @endphp
                                <tr>
                                    <td>{{ optional($transaction->transaction_date)->format('d/m/Y H:i') }}</td>
                                    <td><span class="badge {{ $isPositive ? 'badge-light-success' : 'badge-light-danger' }}">{{ $transaction->type_text }}</span></td>
                                    <td class="{{ $isPositive ? 'text-success' : 'text-danger' }}">{{ $isPositive ? '+' : '-' }}{{ $transaction->quantity }}</td>
                                    <td>{{ $transaction->new_stock }}</td>
                                    <td>{{ $transaction->performed_by ?: '-' }}</td>
                                    <td>{{ $transaction->description ?: $transaction->notes ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-10 text-muted">Henüz hareket geçmişi yok.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end pt-4">
                    {!! $selectedTransactions->links('pagination::bootstrap-4') !!}
                </div>
            @elseif($activeDetailTab === 'analytics')
                <div class="text-center text-muted py-10">
                    Son işlemlere göre stok değişim noktası: {{ $chartSeries->last()['value'] ?? 0 }}
                </div>
            @else
                <div class="row g-6">
                    <div class="col-md-6">
                        <div class="mb-5"><div class="text-muted fs-7">Ürün Adı</div><div class="fw-bold fs-5">{{ $selectedProduct->name }}</div></div>
                        <div class="mb-5"><div class="text-muted fs-7">Kategori</div><div class="fw-bold fs-6">{{ $selectedProduct->category ?: '-' }}</div></div>
                        <div class="mb-5"><div class="text-muted fs-7">SKU / Barkod</div><div class="fw-bold fs-6">{{ $selectedProduct->sku ?: '-' }}</div></div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-5"><div class="text-muted fs-7">Marka</div><div class="fw-bold fs-6">{{ $selectedProduct->brand ?: '-' }}</div></div>
                        <div class="mb-5"><div class="text-muted fs-7">Takip Tipi</div><div class="fw-bold fs-6">{{ $detailMeta['tracking_type'] }}</div></div>
                        <div class="mb-5"><div class="text-muted fs-7">Ağırlıklı Ortalama Alış</div><div class="fw-bold fs-6">{{ number_format($detailMeta['weighted_average_price'], 2, '.', ',') }} TRY</div></div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted fs-7">Açıklama</div>
                        <div class="fw-semibold">{{ $selectedProduct->description ?: '-' }}</div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@elseif($modalMode === 'adjust' && $selectedProduct)
    <div class="modal-content">
        <form id="stockAdjustForm" method="POST" action="{{ route('products.adjust-stock', $selectedProduct) }}">
            @csrf
            <input type="hidden" name="product_id" value="{{ $selectedProduct->id }}" />
            <div class="modal-header border-0 pb-0">
                <h3 class="fw-bold">Stok Miktarı Ayarla</h3>
                <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body py-8 px-lg-17">
                <div class="alert alert-primary py-3 mb-6">
                    Mevcut Miktar: <strong>{{ $selectedProduct->total_stock }} {{ strtolower($selectedProduct->unit) }}</strong>
                </div>

                <div class="mb-6">
                    <label class="form-label required">İşlem Tipi</label>
                    <select name="operation_type" class="form-select form-select-solid" data-control="select2" data-hide-search="true" required>
                        <option value="">İşlem tipi seçin</option>
                        <option value="increase">Stok Girişi</option>
                        <option value="decrease">Stok Çıkışı</option>
                        <option value="sync">Stoku Eşitle</option>
                    </select>
                </div>
                <div class="mb-6">
                    <label class="form-label required">Miktar</label>
                    <input type="number" min="1" name="quantity" class="form-control form-control-solid" required />
                </div>
                <div class="mb-6">
                    <label class="form-label required">Sebep</label>
                    <select name="reason" class="form-select form-select-solid" data-control="select2" required>
                        <option value="">Sebep seçin</option>
                        <option value="Ilk stok girisi">İlk stok girişi</option>
                        <option value="Sayim duzeltmesi">Sayım düzeltmesi</option>
                        <option value="Kullanim dusumu">Kullanım düşümü</option>
                        <option value="Hasar / fire">Hasar / fire</option>
                        <option value="Transfer / teslim">Transfer / teslim</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Notlar</label>
                    <textarea name="notes" class="form-control form-control-solid" rows="4" placeholder="Ek notlar (opsiyonel)"></textarea>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">İptal</button>
                <button type="submit" class="btn btn-primary" id="stockAdjustSubmit">
                    <span class="indicator-label">Uygula</span>
                    <span class="indicator-progress">Lütfen bekleyin... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                </button>
            </div>
        </form>
    </div>
@endif
