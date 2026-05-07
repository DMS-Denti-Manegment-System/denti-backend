<x-page-toolbar>
    <div class="col-md-5 col-lg-4"><label class="form-label">Ara</label><input type="text" name="search" class="form-control form-control-solid" value="{{ request('search') }}" placeholder="Baslik veya mesaj" /></div>
    <div class="col-md-3 col-lg-2"><label class="form-label">Tip</label><select name="type" class="form-select form-select-solid"><option value="">Tum tipler</option>@foreach(['low_stock' => 'Dusuk stok', 'critical_stock' => 'Kritik stok', 'expired' => 'Suresi gecmis', 'near_expiry' => 'Yaklasan SKT'] as $value => $label)<option value="{{ $value }}" @selected(request('type') === $value)>{{ $label }}</option>@endforeach</select></div>
    <div class="col-md-3 col-lg-2"><label class="form-label">Durum</label><select name="resolved" class="form-select form-select-solid"><option value="">Tum durumlar</option><option value="0" @selected(request('resolved') === '0')>Aktif</option><option value="1" @selected(request('resolved') === '1')>Cozulmus</option></select></div>
    <div class="col text-end align-self-end d-flex gap-2 justify-content-end">
        <a href="{{ route('alerts.sync') }}" class="btn btn-light-info" data-module-action>
            <i class="ki-duotone ki-arrows-loop fs-2"><span class="path1"></span><span class="path2"></span></i> Tara
        </a>
        <a href="{{ route('alerts.settings') }}" class="btn btn-light-primary">
            <i class="ki-duotone ki-setting-2 fs-2"><span class="path1"></span><span class="path2"></span></i> Ayarlar
        </a>
    </div>
</x-page-toolbar>
