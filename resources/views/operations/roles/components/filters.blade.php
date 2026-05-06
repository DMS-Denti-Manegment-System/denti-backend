<x-page-toolbar>
    <div class="col-md-6 col-lg-4">
        <label class="form-label">Ara</label>
        <input type="text" class="form-control form-control-solid" id="roleSearch" name="search" value="{{ request('search') }}" placeholder="Rol veya izin ara" />
    </div>
    <div class="col-md-3 col-lg-2 ms-auto">
        <a href="{{ route('roles.create') }}" class="btn btn-primary w-100" data-module-create>Yeni Rol</a>
    </div>
</x-page-toolbar>
