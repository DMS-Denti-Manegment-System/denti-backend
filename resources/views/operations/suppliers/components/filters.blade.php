<x-page-toolbar>
    <div class="col-md-6 col-lg-4">
        <label class="form-label">Ara</label>
        <input type="text" name="search" class="form-control form-control-solid" value="{{ request('search') }}" placeholder="Firma veya iletisim" />
    </div>
    <div class="col-md-6 col-lg-3">
        <label class="form-label">Durum</label>
        <select name="status" class="form-select form-select-solid">
            <option value="">Tum durumlar</option>
            <option value="active" @selected(request('status') === 'active')>Aktif</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Pasif</option>
        </select>
    </div>
    <div class="col-md-3 col-lg-2 ms-auto">
        <a href="{{ route('suppliers.create') }}" class="btn btn-primary w-100" data-module-create>Yeni Tedarikci</a>
    </div>
</x-page-toolbar>
