@if(in_array($modalMode, ['create', 'edit'], true))
    <div class="modal fade" id="clinicModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST" action="{{ $modalMode === 'edit' ? route('clinics.update', $editingClinic) : route('clinics.store') }}">
                    @csrf
                    @if($modalMode === 'edit')
                        @method('PUT')
                    @endif
                    <div class="modal-header">
                        <h2>{{ $modalMode === 'edit' ? 'Klinik Duzenle' : 'Yeni Klinik' }}</h2>
                        <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body py-10 px-lg-17">
                        <div class="row g-5">
                            <div class="col-md-6"><label class="form-label">Klinik Adi</label><input class="form-control form-control-solid" name="name" value="{{ old('name', $editingClinic?->name) }}" required /></div>
                            <div class="col-md-6"><label class="form-label">Sorumlu</label><input class="form-control form-control-solid" name="responsible_person" value="{{ old('responsible_person', $editingClinic?->responsible_person) }}" /></div>
                            <div class="col-md-6"><label class="form-label">Telefon</label><input class="form-control form-control-solid" name="phone" value="{{ old('phone', $editingClinic?->phone) }}" /></div>
                            <div class="col-md-6"><label class="form-label">E-posta</label><input class="form-control form-control-solid" name="email" value="{{ old('email', $editingClinic?->email) }}" /></div>
                            <div class="col-md-4"><label class="form-label">Sehir</label><input class="form-control form-control-solid" name="city" value="{{ old('city', $editingClinic?->city) }}" /></div>
                            <div class="col-md-4"><label class="form-label">Ilce</label><input class="form-control form-control-solid" name="district" value="{{ old('district', $editingClinic?->district) }}" /></div>
                            <div class="col-md-4"><label class="form-label">Posta Kodu</label><input class="form-control form-control-solid" name="postal_code" value="{{ old('postal_code', $editingClinic?->postal_code) }}" /></div>
                            <div class="col-12"><label class="form-label">Adres</label><textarea class="form-control form-control-solid" name="address" rows="3">{{ old('address', $editingClinic?->address) }}</textarea></div>
                            <div class="col-12"><label class="form-label">Aciklama</label><textarea class="form-control form-control-solid" name="description" rows="4">{{ old('description', $editingClinic?->description) }}</textarea></div>
                            <div class="col-md-6"><label class="form-label">Konum</label><input class="form-control form-control-solid" name="location" value="{{ old('location', $editingClinic?->location) }}" /></div>
                            <div class="col-md-6 d-flex align-items-center"><label class="form-check form-check-custom form-check-solid mt-8"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingClinic?->is_active ?? true)) /><span class="form-check-label">Aktif</span></label></div>
                        </div>
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Iptal</button>
                        <button type="submit" class="btn btn-primary">{{ $modalMode === 'edit' ? 'Guncelle' : 'Olustur' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
