<div class="mb-8">
    <div class="py-5">
        <form id="stockFilterForm" class="row g-4 align-items-end">
            <div class="col-xl-3 col-lg-6">
                <label class="form-label fw-bold">Ara</label>
                <div class="input-group input-group-solid">
                    <input type="text" name="search" class="form-control" placeholder="Ürün adı, SKU veya Marka..." value="{{ request('search') }}" />
                    <button class="btn btn-icon btn-light" type="submit">
                        <i class="ki-duotone ki-magnifier fs-2"></i>
                    </button>
                </div>
            </div>
            
            <div class="col-xl-2 col-lg-6">
                <label class="form-label fw-bold">Klinik</label>
                <select name="clinic_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Klinik Seçin">
                    <option value="">Tüm Klinikler</option>
                    @foreach ($clinics as $clinic)
                        <option value="{{ $clinic->id }}" @selected(request('clinic_id') == $clinic->id)>{{ $clinic->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="col-xl-2 col-lg-6">
                <label class="form-label fw-bold">Kategori</label>
                <select name="category" class="form-select form-select-solid" data-control="select2" data-placeholder="Kategori Seçin">
                    <option value="">Tüm Kategoriler</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->name }}" @selected(request('category') == $category->name)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div class="col-xl-2 col-lg-6">
                <label class="form-label fw-bold">Seviye</label>
                <select name="level" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                    <option value="">Tüm Seviyeler</option>
                    <option value="normal" @selected(request('level') === 'normal')>Normal</option>
                    <option value="low" @selected(request('level') === 'low')>Düşük</option>
                    <option value="critical" @selected(request('level') === 'critical')>Kritik</option>
                </select>
            </div>
            
            <div class="col-xl-2 col-lg-6">
                <label class="form-label fw-bold">Durum</label>
                <select name="status" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                    <option value="">Tüm Durumlar</option>
                    <option value="active" @selected(request('status', 'active') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Pasif</option>
                </select>
            </div>
            
            <div class="col-xl-1 d-flex justify-content-xl-end">
                <button type="button" id="resetFilters" class="btn btn-light w-100" title="Temizle">
                    <i class="ki-duotone ki-arrows-loop fs-2"><span class="path1"></span><span class="path2"></span></i>
                </button>
            </div>
            
            <div class="col-12 d-flex flex-wrap justify-content-between align-items-center gap-3 pt-2">
                <div class="d-flex gap-2">
                    <a href="{{ route('categories.index') }}" class="btn btn-sm btn-light-primary">
                        <i class="ki-duotone ki-tag fs-4 me-1"></i> Kategoriler
                    </a>
                    <a href="{{ route('suppliers.index') }}" class="btn btn-sm btn-light-info">
                        <i class="ki-duotone ki-delivery-3 fs-4 me-1"></i> Tedarikçiler
                    </a>
                </div>
                <button type="button" class="btn btn-primary" id="btnCreateStock">
                    <i class="ki-duotone ki-plus fs-3 me-1"></i> Yeni Stok Ekle
                </button>
            </div>
        </form>
    </div>
</div>
