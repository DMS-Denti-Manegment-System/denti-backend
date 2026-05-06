<div class="row g-6 mb-8">
    <div class="col-xl-2 col-md-4 col-6">
        <div class="h-100 bg-transparent">
            <div class="d-flex flex-column justify-content-center px-6 py-5">
                <span class="material-symbols-outlined text-primary fs-2hx mb-2">inventory_2</span>
                <div class="app-stats-card__value">{{ $stockStats['total_items'] }}</div>
                <div class="app-stats-card__label">Toplam Ürün</div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="h-100 bg-transparent {{ $stockStats['low_stock_items'] > 0 ? 'border-warning border-dashed border' : '' }} rounded">
            <div class="d-flex flex-column justify-content-center px-6 py-5">
                <span class="material-symbols-outlined text-warning fs-2hx mb-2">warning</span>
                <div class="app-stats-card__value text-warning">{{ $stockStats['low_stock_items'] }}</div>
                <div class="app-stats-card__label">Düşük Stok</div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="h-100 bg-transparent {{ $stockStats['critical_stock_items'] > 0 ? 'border-danger border-dashed border' : '' }} rounded">
            <div class="d-flex flex-column justify-content-center px-6 py-5">
                <span class="material-symbols-outlined text-danger fs-2hx mb-2">emergency</span>
                <div class="app-stats-card__value text-danger">{{ $stockStats['critical_stock_items'] }}</div>
                <div class="app-stats-card__label">Kritik Stok</div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="h-100 bg-transparent {{ $stockStats['low_expiring_items'] > 0 ? 'border-warning border-dashed border' : '' }} rounded">
            <div class="d-flex flex-column justify-content-center px-6 py-5">
                <span class="material-symbols-outlined text-warning fs-2hx mb-2">event_upcoming</span>
                <div class="app-stats-card__value text-warning">{{ $stockStats['low_expiring_items'] }}</div>
                <div class="app-stats-card__label">Yaklaşan Miyat</div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="h-100 bg-transparent {{ $stockStats['critical_expiring_items'] > 0 ? 'border-danger border-dashed border' : '' }} rounded">
            <div class="d-flex flex-column justify-content-center px-6 py-5">
                <span class="material-symbols-outlined text-danger fs-2hx mb-2">event_busy</span>
                <div class="app-stats-card__value text-danger">{{ $stockStats['critical_expiring_items'] }}</div>
                <div class="app-stats-card__label">Kritik Miyat</div>
            </div>
        </div>
    </div>

    <div class="col-xl-2 col-md-4 col-6">
        <div class="h-100 bg-transparent">
            <div class="d-flex flex-column justify-content-center px-6 py-5">
                <span class="material-symbols-outlined text-success fs-2hx mb-2">payments</span>
                <div class="app-stats-card__value text-success">{{ number_format($stockStats['total_value'], 2) }} <small class="fs-7">TL</small></div>
                <div class="app-stats-card__label">Stok Değeri</div>
            </div>
        </div>
    </div>
</div>
