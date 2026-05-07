@if(in_array($modalMode, ['create', 'edit'], true))
    <div class="modal fade" id="supplierModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ $modalMode === 'edit' ? route('suppliers.update', $editingSupplier) : route('suppliers.store') }}">
                    @csrf
                    @if($modalMode === 'edit')
                        @method('PUT')
                    @endif
                    <div class="modal-header">
                        <h2>{{ $modalMode === 'edit' ? 'Tedarikci Duzenle' : 'Yeni Tedarikci' }}</h2>
                        <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body py-10 px-lg-17">
                        <div class="row g-5">
                            <div class="col-md-6"><label class="form-label">Firma</label><input class="form-control form-control-solid" name="name" value="{{ old('name', $editingSupplier?->name) }}" required /></div>
                            <div class="col-md-6"><label class="form-label">Iletisim Kisisi</label><input class="form-control form-control-solid" name="contact_person" value="{{ old('contact_person', $editingSupplier?->contact_person) }}" /></div>
                            <div class="col-md-6"><label class="form-label">Telefon</label><input class="form-control form-control-solid" name="phone" value="{{ old('phone', $editingSupplier?->phone) }}" /></div>
                            <div class="col-md-6"><label class="form-label">E-posta</label><input class="form-control form-control-solid" name="email" value="{{ old('email', $editingSupplier?->email) }}" /></div>
                            <div class="col-md-6"><label class="form-label">Vergi No</label><input class="form-control form-control-solid" name="tax_number" value="{{ old('tax_number', $editingSupplier?->tax_number) }}" /></div>
                            <div class="col-md-6 d-flex align-items-center"><label class="form-check form-check-custom form-check-solid mt-8"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingSupplier?->is_active ?? true)) /><span class="form-check-label">Aktif</span></label></div>
                            <div class="col-12"><label class="form-label">Adres</label><textarea class="form-control form-control-solid" name="address" rows="4">{{ old('address', $editingSupplier?->address) }}</textarea></div>
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
@elseif($modalMode === 'detail' && isset($selectedSupplier) && $selectedSupplier)
    <div class="modal fade show" id="supplierModal" tabindex="-1" aria-modal="true" role="dialog" style="display:block;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h2>Tedarikci Detayi</h2><a href="{{ route('suppliers.index') }}" class="btn btn-sm btn-icon"><i class="ki-duotone ki-cross fs-1"></i></a></div>
                <div class="modal-body">
                    <div><strong>Firma:</strong> {{ $selectedSupplier->name }}</div>
                    <div><strong>Iletisim:</strong> {{ $selectedSupplier->contact_person ?: '-' }}</div>
                    <div><strong>Telefon:</strong> {{ $selectedSupplier->phone ?: '-' }}</div>
                    <div><strong>E-posta:</strong> {{ $selectedSupplier->email ?: '-' }}</div>
                    <div><strong>Adres:</strong> {{ $selectedSupplier->address ?: '-' }}</div>
                    <hr>
                    <div><strong>Toplam Stok:</strong> {{ $supplierDetailStats['total_stocks'] ?? 0 }}</div>
                    <div><strong>Aktif Stok:</strong> {{ $supplierDetailStats['active_stocks'] ?? 0 }}</div>
                    <div><strong>Pasif Stok:</strong> {{ $supplierDetailStats['passive_stocks'] ?? 0 }}</div>
                </div>
            </div>
        </div>
    </div>
@endif
