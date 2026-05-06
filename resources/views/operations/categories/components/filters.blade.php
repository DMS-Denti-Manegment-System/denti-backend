<x-page-toolbar>
    <div class="col-md-6 col-lg-4">
        <label class="form-label">Ara</label>
        <input type="text" name="search" class="form-control form-control-solid" value="{{ request('search') }}" placeholder="Kategori adi" />
    </div>
    <div class="col-md-3 col-lg-2 ms-auto">
        <a href="{{ route('categories.create') }}" class="btn btn-primary w-100" data-module-create>Yeni Kategori</a>
    </div>
</x-page-toolbar>
