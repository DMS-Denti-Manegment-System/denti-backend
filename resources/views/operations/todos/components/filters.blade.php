<x-page-toolbar>
    <div class="col-md-6 col-lg-4"><label class="form-label">Ara</label><input type="text" name="search" class="form-control form-control-solid" value="{{ request('search') }}" placeholder="Todo basligi" /></div>
    <div class="col-md-3 col-lg-2"><label class="form-label">Durum</label><select name="status" class="form-select form-select-solid"><option value="">Tum durumlar</option><option value="open" @selected(request('status') === 'open')>Bekliyor</option><option value="completed" @selected(request('status') === 'completed')>Tamamlandi</option></select></div>
    <div class="col-md-3 col-lg-2 ms-auto"><a href="{{ route('todos.create') }}" class="btn btn-primary w-100" data-module-create>Yeni Todo</a></div>
</x-page-toolbar>
