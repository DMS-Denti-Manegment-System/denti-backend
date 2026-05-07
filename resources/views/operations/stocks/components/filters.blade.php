<div class="app-stock-filter-card mb-6">
    <form id="stockFilterForm" class="app-stock-filter-bar">
        <div class="app-stock-filter-bar__fields">
            <div class="app-stock-search">
                <input type="text" name="search" class="form-control form-control-solid"
                    placeholder="Stok adi ile ara..." value="{{ request('search') }}" />
                <button class="btn btn-icon btn-light" type="submit" aria-label="Ara">
                    <i class="ki-duotone ki-magnifier fs-3">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>
            </div>

            <select name="clinic_id" class="form-select form-select-solid" data-control="select2"
                data-placeholder="Klinik Seçin">
                <option value="">Klinik Seçin</option>
                @foreach ($clinics as $clinic)
                    <option value="{{ $clinic->id }}" @selected(request('clinic_id') == $clinic->id)>{{ $clinic->name }}</option>
                @endforeach
            </select>

            <select name="category" class="form-select form-select-solid" data-control="select2"
                data-placeholder="Kategori">
                <option value="">Kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->name }}" @selected(request('category') == $category->name)>{{ $category->name }}</option>
                @endforeach
            </select>

            <select name="level" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                <option value="">Seviye</option>
                <option value="low_stock" @selected(request('level') === 'low_stock' || request('level') === 'low')>Düşük Seviye Stok</option>
                <option value="critical_stock" @selected(request('level') === 'critical_stock' || request('level') === 'critical')>Kritik Seviye Stok</option>
                <option value="low_expiry" @selected(request('level') === 'low_expiry' || request('level') === 'near_expiry')>Düşük Seviye Miad</option>
                <option value="critical_expiry" @selected(request('level') === 'critical_expiry')>Kritik Seviye Miad</option>
                <option value="expired" @selected(request('level') === 'expired')>Süresi Geçmiş</option>
            </select>

            <select name="status" class="form-select form-select-solid" data-control="select2" data-hide-search="true">
                <option value="">Durum</option>
                <option value="active" @selected(request('status', 'active') === 'active')>Aktif</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Pasif</option>
            </select>
            
            <div class="d-flex gap-2">
                <button type="button" id="resetFilters" class="btn btn-light w-100">Temizle</button>

                <a href="{{ route('stocks.create') }}" class="btn btn-primary w-100 text-nowrap" data-module-create>
                    <i class="ki-duotone ki-plus fs-4 me-1"><span class="path1"></span><span class="path2"></span></i>
                    Yeni Stok
                </a>
            </div>
        </div>
    </form>
</div>
