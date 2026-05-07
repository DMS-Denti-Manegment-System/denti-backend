<x-page-toolbar>
    <div class="col-md-4 col-lg-3">
        <label class="form-label">Ürün Ara</label>
        <input type="text" name="search" class="form-control form-control-solid" placeholder="Stok adı ile ara..." value="{{ request('search') }}" />
    </div>

    <div class="col-md-3 col-lg-2">
        <label class="form-label">Klinik</label>
        <select name="clinic_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Klinik Seçin">
            <option value="">Klinik Seçin</option>
            @foreach ($clinics as $clinic)
                <option value="{{ $clinic->id }}" @selected(request('clinic_id') == $clinic->id)>{{ $clinic->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3 col-lg-2">
        <label class="form-label">Kategori</label>
        <select name="category" class="form-select form-select-solid" data-control="select2" data-placeholder="Kategori">
            <option value="">Kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->name }}" @selected(request('category') == $category->name)>{{ $category->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3 col-lg-2">
        <label class="form-label">Durum / Seviye</label>
        <select name="level" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
            <option value="">Tümü</option>
            <option value="low_stock" @selected(request('level') === 'low_stock' || request('level') === 'low')>Düşük Seviye Stok</option>
            <option value="critical_stock" @selected(request('level') === 'critical_stock' || request('level') === 'critical')>Kritik Seviye Stok</option>
            <option value="low_expiry" @selected(request('level') === 'low_expiry' || request('level') === 'near_expiry')>Düşük Seviye Miad</option>
            <option value="critical_expiry" @selected(request('level') === 'critical_expiry')>Kritik Seviye Miad</option>
            <option value="expired" @selected(request('level') === 'expired')>Süresi Geçmiş</option>
        </select>
    </div>

    <div class="col text-end align-self-end d-flex gap-2 justify-content-end">
        <button type="button" id="resetFilters" class="btn btn-light-primary">
            <i class="ki-duotone ki-arrows-loop fs-2"><span class="path1"></span><span class="path2"></span></i>
            Temizle
        </button>

        <a href="{{ route('stocks.create') }}" class="btn btn-primary" data-module-create>
            <i class="ki-duotone ki-plus fs-2"><span class="path1"></span><span class="path2"></span></i>
            Yeni Stok
        </a>
    </div>
</x-page-toolbar>
