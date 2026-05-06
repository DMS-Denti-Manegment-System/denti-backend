@php
    $modalMode = $modalMode ?? (isset($editingProduct) ? 'edit' : 'create');
    $hasExpirationDate = (bool) old('has_expiration_date', isset($editingProduct) && $editingProduct->has_expiration_date);
    
    $selectedClinicId = $editingBatch?->clinic_id ?? $editingProduct?->clinic_id ?? null;
    $selectedSupplierId = $editingBatch?->supplier_id ?? null;
    $selectedCurrency = $editingBatch?->currency ?? 'TRY';
    $quantity = $editingBatch?->current_stock ?? 0;
    
    $purchaseDate = isset($editingBatch?->purchase_date) ? $editingBatch->purchase_date->format('Y-m-d') : now()->format('Y-m-d');
    $expiryDate = isset($editingBatch?->expiry_date) ? $editingBatch->expiry_date->format('Y-m-d') : '';
@endphp

<div class="modal-content">
    <form id="stockForm" method="POST" action="{{ $modalMode === 'edit' ? route('stocks.update', $editingProduct) : route('stocks.store') }}">
        @csrf
        @if($modalMode === 'edit')
            @method('PUT')
        @endif

        <div class="modal-header border-0 pb-0">
            <h2 class="fw-bold">{{ $modalMode === 'edit' ? 'Ürünü Düzenle' : 'Yeni Ürün Ekle' }}</h2>
            <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
            </div>
        </div>

        <div class="modal-body py-10 px-lg-17">
            <div class="scroll-y me-n7 pe-7" id="stockModalScroll" data-kt-scroll="true" data-kt-scroll-activate="{default: false, lg: true}" data-kt-scroll-max-height="auto" data-kt-scroll-dependencies="#stockModalHeader" data-kt-scroll-wrappers="#stockModalScroll" data-kt-scroll-offset="300px">
                
                <!-- Product Information -->
                <div class="mb-10">
                    <h4 class="fw-bold mb-5">Ürün Bilgileri</h4>
                    <div class="row g-5">
                        <div class="col-md-8">
                            <label class="required form-label">Ürün Adı</label>
                            <input type="text" name="name" class="form-control form-control-solid" value="{{ $editingProduct?->name }}" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Barkod / SKU</label>
                            <input type="text" name="sku" class="form-control form-control-solid" value="{{ $editingProduct?->sku }}" />
                        </div>
                    </div>
                    <div class="row g-5 mt-2">
                        <div class="col-md-6">
                            <label class="required form-label">Birim</label>
                            <select name="unit" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                                @foreach($units as $u)
                                    <option value="{{ $u }}" @selected(($editingProduct?->unit ?? 'Adet') === $u)>{{ $u }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kategori</label>
                            <select name="category" class="form-select form-select-solid" data-control="select2">
                                <option value="">Kategori Seçin</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->name }}" @selected(($editingProduct?->category ?? '') === $cat->name)>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Stock Settings -->
                <div class="mb-10">
                    <h4 class="fw-bold mb-5">Stok & Depo Ayarları</h4>
                    <div class="row g-5">
                        <div class="col-md-6">
                            <label class="required form-label">Klinik</label>
                            <select name="clinic_id" class="form-select form-select-solid" data-control="select2">
                                <option value="">Klinik Seçin</option>
                                @foreach($clinics as $clinic)
                                    <option value="{{ $clinic->id }}" @selected($selectedClinicId == $clinic->id)>{{ $clinic->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="required form-label">Tedarikçi</label>
                            <select name="supplier_id" class="form-select form-select-solid" data-control="select2">
                                <option value="">Tedarikçi Seçin</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected($selectedSupplierId == $supplier->id)>{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-5 mt-2">
                        <div class="col-md-4">
                            <label class="required form-label">Miktar</label>
                            <input type="number" name="quantity" class="form-control form-control-solid" value="{{ $quantity }}" required />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Alış Fiyatı</label>
                            <input type="number" step="0.01" name="purchase_price" class="form-control form-control-solid" value="{{ $editingBatch?->purchase_price }}" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Döviz</label>
                            <select name="currency" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                                @foreach($currencies as $code => $label)
                                    <option value="{{ $code }}" @selected($selectedCurrency === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-5 mt-2">
                         <div class="col-md-6">
                            <label class="form-label">Alış Tarihi</label>
                            <input type="date" name="purchase_date" class="form-control form-control-solid" value="{{ $purchaseDate }}" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Depo Konumu</label>
                            <input type="text" name="storage_location" class="form-control form-control-solid" value="{{ $editingBatch?->storage_location }}" placeholder="R-1, Raf 2 vb." />
                        </div>
                    </div>
                </div>

                <!-- Expiry & Alerts -->
                <div class="mb-10">
                    <h4 class="fw-bold mb-5">Takip & Uyarı Seviyeleri</h4>
                    <div class="row g-5">
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid mb-3">
                                <input type="hidden" name="has_expiration_date" value="0" />
                                <input class="form-check-input" type="checkbox" name="has_expiration_date" value="1" id="expiry_toggle" @checked($hasExpirationDate) />
                                <span class="form-check-label fw-bold text-gray-700">SKT Takibi Yapılsın mı?</span>
                            </label>
                            <div id="expiry_date_container" class="{{ $hasExpirationDate ? '' : 'd-none' }} mt-3">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fs-8 text-muted">Son Kullanma Tarihi</label>
                                        <input type="date" name="expiry_date" class="form-control form-control-solid" value="{{ $expiryDate }}" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fs-8 text-muted">Miyat Uyarı Günü (S/K)</label>
                                        <div class="d-flex gap-2">
                                            <input type="number" name="expiry_yellow_days" class="form-control form-control-solid" value="{{ $editingBatch?->expiry_yellow_days ?? 30 }}" title="Sarı Uyarı (Gün)" />
                                            <input type="number" name="expiry_red_days" class="form-control form-control-solid" value="{{ $editingBatch?->expiry_red_days ?? 15 }}" title="Kırmızı Uyarı (Gün)" />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Kritik Stok Seviyesi</label>
                            <div class="d-flex gap-3">
                                <input type="number" name="yellow_alert_level" class="form-control form-control-solid" value="{{ $editingProduct?->yellow_alert_level ?? 10 }}" placeholder="Sarı" title="Sarı Uyarı" />
                                <input type="number" name="red_alert_level" class="form-control form-control-solid" value="{{ $editingProduct?->red_alert_level ?? 5 }}" placeholder="Kırmızı" title="Kırmızı Uyarı" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sub-unit (İç İçe Stok) -->
                <div class="mb-5">
                    <h4 class="fw-bold mb-5">İç İçe Stok (Alt Birim)</h4>
                    <div class="row g-5">
                        <div class="col-md-6">
                            <label class="form-check form-switch form-check-custom form-check-solid mb-3">
                                <input type="hidden" name="has_sub_unit" value="0" />
                                <input class="form-check-input" type="checkbox" name="has_sub_unit" value="1" id="sub_unit_toggle" @checked($editingProduct?->has_sub_unit) />
                                <span class="form-check-label fw-bold text-gray-700">Alt Birim Kullanılsın mı?</span>
                            </label>
                            <div id="sub_unit_container" class="{{ $editingProduct?->has_sub_unit ? '' : 'd-none' }} mt-3">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fs-8 text-muted">Alt Birim Adı</label>
                                        <input type="text" name="sub_unit_name" class="form-control form-control-solid" value="{{ $editingProduct?->sub_unit_name }}" placeholder="örn: Adet" />
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fs-8 text-muted">Çarpan (1 Ana Birim = ? Alt Birim)</label>
                                        <input type="number" name="sub_unit_multiplier" class="form-control form-control-solid" value="{{ $editingProduct?->sub_unit_multiplier }}" placeholder="örn: 10" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="bg-light-info rounded p-5">
                                <span class="text-info fs-7">
                                    <i class="ki-duotone ki-information-5 fs-2 text-info me-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                    <b>Örnek:</b> Ana birim "Kutu" ise ve içinde 10 tane varsa; Alt Birim Adı "Adet", Çarpan ise "10" olmalıdır.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="modal-footer flex-center">
            <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">İptal</button>
            <button type="submit" class="btn btn-primary" id="stockFormSubmit">
                <span class="indicator-label">Kaydet</span>
                <span class="indicator-progress">Lütfen bekleyin... <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
            </button>
        </div>
    </form>
</div>
