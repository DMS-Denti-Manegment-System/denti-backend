@if(in_array($modalMode, ['create', 'edit'], true))
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ $modalMode === 'edit' ? route('categories.update', $editingCategory) : route('categories.store') }}">
                    @csrf
                    @if($modalMode === 'edit')
                        @method('PUT')
                    @endif
                    <div class="modal-header">
                        <h2>{{ $modalMode === 'edit' ? 'Kategori Duzenle' : 'Yeni Kategori' }}</h2>
                        <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body py-10 px-lg-17">
                        <div class="row g-5">
                            <div class="col-md-8">
                                <label class="form-label">Kategori Adi</label>
                                <input class="form-control form-control-solid" name="name" value="{{ old('name', $editingCategory?->name) }}" required />
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Renk</label>
                                <input class="form-control form-control-solid" name="color" value="{{ old('color', $editingCategory?->color ?? '#6c757d') }}" />
                            </div>
                            <div class="col-12">
                                <label class="form-label">Aciklama</label>
                                <textarea class="form-control form-control-solid" name="description" rows="4">{{ old('description', $editingCategory?->description) }}</textarea>
                            </div>
                            <div class="col-md-6 d-flex align-items-center">
                                <label class="form-check form-check-custom form-check-solid mt-8">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingCategory?->is_active ?? true)) />
                                    <span class="form-check-label">Aktif</span>
                                </label>
                            </div>
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
