<x-page-toolbar>
    <div class="col-md-6 col-lg-4"><label class="form-label">Ara</label><input type="text" name="search" class="form-control form-control-solid" value="{{ request('search') }}" placeholder="Talep no, kullanici veya sebep" /></div>
    <div class="col-md-6 col-lg-3"><label class="form-label">Durum</label><select name="status" class="form-select form-select-solid"><option value="">Tum durumlar</option>@foreach(['pending' => 'Bekliyor', 'approved' => 'Onaylandi', 'in_transit' => 'Yolda', 'completed' => 'Tamamlandi', 'rejected' => 'Reddedildi'] as $value => $label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-3 col-lg-2 ms-auto"><a href="{{ route('stock-requests.create') }}" class="btn btn-primary w-100" data-module-create>Yeni Talep</a></div>
</x-page-toolbar>
