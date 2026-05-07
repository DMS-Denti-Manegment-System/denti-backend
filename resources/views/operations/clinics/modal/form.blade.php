@if(in_array($modalMode, ['create', 'edit'], true))
    <div class="modal fade" id="clinicModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-800px">
            <div class="modal-content shadow-lg border-0">
                <form method="POST" action="{{ $modalMode === 'edit' ? route('clinics.update', $editingClinic) : route('clinics.store') }}" id="clinicForm">
                    @csrf
                    @if($modalMode === 'edit')
                        @method('PUT')
                    @endif
                    
                    <div class="modal-header pb-0 border-0 justify-content-end">
                        <div class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                        </div>
                    </div>

                    <div class="modal-body scroll-y px-10 px-lg-15 pt-0 pb-15">
                        <div class="mb-13 text-center">
                            <h1 class="mb-3">{{ $modalMode === 'edit' ? 'Klinik Bilgilerini Düzenle' : 'Yeni Klinik Ekle' }}</h1>
                            <div class="text-muted fw-semibold fs-5">Klinik temel ve iletişim bilgilerini buradan yönetebilirsiniz.</div>
                        </div>

                        <!-- Temel Bilgiler -->
                        <div class="d-flex flex-stack mb-5 border-bottom border-bottom-dashed pb-2">
                            <div class="fw-bold fs-4">1. Temel Bilgiler</div>
                        </div>
                        
                        <div class="row g-9 mb-8">
                            <div class="col-md-12 fv-row">
                                <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                                    <span class="required">Klinik Adı</span>
                                </label>
                                <input type="text" class="form-control form-control-solid" placeholder="Örn: Muğla Dent48" name="name" value="{{ old('name', $editingClinic?->name) }}" required />
                            </div>
                            
                            <div class="col-md-12 fv-row">
                                <label class="fs-6 fw-semibold mb-2">Açıklama</label>
                                <textarea class="form-control form-control-solid" rows="3" name="description" placeholder="Klinik hakkında kısa bilgi...">{{ old('description', $editingClinic?->description) }}</textarea>
                                <div class="text-muted fs-8 mt-1 text-end">0 / 1000</div>
                            </div>
                        </div>

                        <!-- İletişim Bilgileri -->
                        <div class="d-flex flex-stack mb-5 border-bottom border-bottom-dashed pb-2">
                            <div class="fw-bold fs-4">2. İletişim Bilgileri</div>
                        </div>

                        <div class="row g-9 mb-8">
                            <div class="col-md-6 fv-row">
                                <label class="fs-6 fw-semibold mb-2">Telefon</label>
                                <input type="text" class="form-control form-control-solid" placeholder="05XX XXX XX XX" name="phone" value="{{ old('phone', $editingClinic?->phone) }}" />
                            </div>
                            <div class="col-md-6 fv-row">
                                <label class="fs-6 fw-semibold mb-2">E-mail</label>
                                <input type="email" class="form-control form-control-solid" placeholder="klinik@example.com" name="email" value="{{ old('email', $editingClinic?->email) }}" />
                            </div>
                            <div class="col-md-12 fv-row">
                                <label class="fs-6 fw-semibold mb-2">Website</label>
                                <input type="text" class="form-control form-control-solid" placeholder="www.klinik.com" name="website" value="{{ old('website', $editingClinic?->website) }}" />
                            </div>
                        </div>

                        <!-- Adres Bilgileri -->
                        <div class="d-flex flex-stack mb-5 border-bottom border-bottom-dashed pb-2">
                            <div class="fw-bold fs-4">3. Adres Bilgileri</div>
                        </div>

                        <div class="row g-9">
                            <div class="col-md-4 fv-row">
                                <label class="fs-6 fw-semibold mb-2">Şehir</label>
                                <input type="text" class="form-control form-control-solid" placeholder="Şehir" name="city" value="{{ old('city', $editingClinic?->city) }}" />
                            </div>
                            <div class="col-md-4 fv-row">
                                <label class="fs-6 fw-semibold mb-2">İlçe</label>
                                <input type="text" class="form-control form-control-solid" placeholder="İlçe" name="district" value="{{ old('district', $editingClinic?->district) }}" />
                            </div>
                            <div class="col-md-4 fv-row">
                                <label class="fs-6 fw-semibold mb-2">Posta Kodu</label>
                                <input type="text" class="form-control form-control-solid" placeholder="Posta Kodu" name="postal_code" value="{{ old('postal_code', $editingClinic?->postal_code) }}" />
                            </div>
                            <div class="col-md-12 fv-row">
                                <label class="fs-6 fw-semibold mb-2">Adres</label>
                                <textarea class="form-control form-control-solid" rows="2" name="address" placeholder="Tam adres...">{{ old('address', $editingClinic?->address) }}</textarea>
                            </div>
                        </div>
                        
                        <input type="hidden" name="is_active" value="1" />
                    </div>

                    <div class="modal-footer flex-center border-0">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">{{ $modalMode === 'edit' ? 'Değişiklikleri Kaydet' : 'Klinik Oluştur' }}</span>
                            <span class="indicator-progress">Lütfen bekleyin...
                            <span class="spinner-border spinner-border-sm align-middle ms-2"></span></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@elseif($modalMode === 'detail' && isset($selectedClinic) && $selectedClinic)
    <div class="modal fade show" id="clinicModal" tabindex="-1" aria-modal="true" role="dialog" style="display:block;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h2>Klinik Detayi</h2><a href="{{ route('clinics.index') }}" class="btn btn-sm btn-icon"><i class="ki-duotone ki-cross fs-1"></i></a></div>
                <div class="modal-body">
                    <div><strong>Klinik:</strong> {{ $selectedClinic->name }}</div>
                    <div><strong>Sorumlu:</strong> {{ $selectedClinic->responsible_person ?: '-' }}</div>
                    <div><strong>Telefon:</strong> {{ $selectedClinic->phone ?: '-' }}</div>
                    <div><strong>Sehir:</strong> {{ $selectedClinic->city ?: '-' }}</div>
                    <div><strong>Adres:</strong> {{ $selectedClinic->address ?: '-' }}</div>
                    <hr>
                    <div><strong>Toplam Stok:</strong> {{ $clinicDetailStats['total_stocks'] ?? 0 }}</div>
                    <div><strong>Toplam Urun:</strong> {{ $clinicDetailStats['total_products'] ?? 0 }}</div>
                    <div><strong>Toplam Talep:</strong> {{ $clinicDetailStats['total_requests'] ?? 0 }}</div>
                    <div><strong>Toplam Uyari:</strong> {{ $clinicDetailStats['total_alerts'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>
@endif
