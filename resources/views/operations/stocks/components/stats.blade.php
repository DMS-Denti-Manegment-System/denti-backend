<style>
    .app-stock-kpi-grid {
        display: flex !important;
        flex-wrap: nowrap !important;
        gap: 10px !important;
        margin-bottom: 20px !important;
        width: 100% !important;
        overflow-x: auto !important; /* Allow scroll if screen is too small, but stay in one row */
    }
    .app-stock-kpi-card {
        flex: 1 1 0 !important;
        min-width: 120px !important; /* Ensure they don't disappear */
        background: #fff !important;
        border: 1px solid #e9edf3 !important;
        border-radius: 10px !important;
        min-height: 70px !important;
        padding: 10px !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
        box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04) !important;
    }
</style>
<div class="app-stock-kpi-grid">
    <article class="app-stock-kpi-card">
        <div class="app-stock-kpi-card__label">Ürün Adeti</div>
        <div class="app-stock-kpi-card__value">
            <i class="ki-duotone ki-package fs-2 text-gray-700">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            <span>{{ $stockStats['total_items'] }}</span>
        </div>
    </article>

    <article class="app-stock-kpi-card">
        <div class="app-stock-kpi-card__label">Düşük Seviye Stok</div>
        <div class="app-stock-kpi-card__value text-warning">
            <i class="ki-duotone ki-information-4 fs-2">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            <span>{{ $stockStats['low_stock_items'] }}</span>
        </div>
    </article>

    <article class="app-stock-kpi-card app-stock-kpi-card--critical">
        <div class="app-stock-kpi-card__label">Kritik Seviye Stok</div>
        <div class="app-stock-kpi-card__value text-danger">
            <i class="ki-duotone ki-information-5 fs-2">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            <span>{{ $stockStats['critical_stock_items'] }}</span>
        </div>
    </article>

    <article class="app-stock-kpi-card">
        <div class="app-stock-kpi-card__label">Düşük Seviye Miad</div>
        <div class="app-stock-kpi-card__value text-warning">
            <i class="ki-duotone ki-calendar-8 fs-2">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            <span>{{ $stockStats['low_expiring_items'] }}</span>
        </div>
    </article>

    <article class="app-stock-kpi-card app-stock-kpi-card--critical">
        <div class="app-stock-kpi-card__label">Kritik Seviye Miad</div>
        <div class="app-stock-kpi-card__value text-danger">
            <i class="ki-duotone ki-calendar-remove fs-4">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            <span>{{ $stockStats['critical_expiring_items'] }}</span>
        </div>
    </article>

    <article class="app-stock-kpi-card">
        <div class="app-stock-kpi-card__label">Toplam Değer</div>
        <div class="app-stock-kpi-card__value">
            <i class="ki-duotone ki-dollar fs-4 text-gray-700">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            <span>{{ number_format($stockStats['total_value'], 2) }} TL</span>
        </div>
    </article>
</div>
