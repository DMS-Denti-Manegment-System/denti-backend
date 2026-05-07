<div class="row g-5 mb-8">
    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-flush bg-white shadow-sm border-0 rounded-3">
            <div class="card-body p-4 d-flex flex-column">
                <span class="text-gray-500 fw-bold fs-8 text-uppercase mb-1">Ürün Adedi</span>
                <div class="d-flex align-items-center gap-2">
                    <i class="ki-duotone ki-package fs-2 text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <span class="fs-4 fw-bold text-gray-800">{{ $stockStats['total_items'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-flush bg-white shadow-sm border-0 rounded-3">
            <div class="card-body p-4 d-flex flex-column">
                <span class="text-gray-500 fw-bold fs-8 text-uppercase mb-1">Düşük Stok</span>
                <div class="d-flex align-items-center gap-2">
                    <i class="ki-duotone ki-information-4 fs-2 text-warning"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <span class="fs-4 fw-bold text-gray-800">{{ $stockStats['low_stock_items'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-flush bg-white shadow-sm border-0 rounded-3">
            <div class="card-body p-4 d-flex flex-column">
                <span class="text-gray-500 fw-bold fs-8 text-uppercase mb-1">Kritik Stok</span>
                <div class="d-flex align-items-center gap-2">
                    <i class="ki-duotone ki-information-5 fs-2 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <span class="fs-4 fw-bold text-gray-800">{{ $stockStats['critical_stock_items'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-flush bg-white shadow-sm border-0 rounded-3">
            <div class="card-body p-4 d-flex flex-column">
                <span class="text-gray-500 fw-bold fs-8 text-uppercase mb-1">Yaklaşan Miad</span>
                <div class="d-flex align-items-center gap-2">
                    <i class="ki-duotone ki-calendar-8 fs-2 text-warning"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <span class="fs-4 fw-bold text-gray-800">{{ $stockStats['low_expiring_items'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-flush bg-white shadow-sm border-0 rounded-3">
            <div class="card-body p-4 d-flex flex-column">
                <span class="text-gray-500 fw-bold fs-8 text-uppercase mb-1">Kritik Miad</span>
                <div class="d-flex align-items-center gap-2">
                    <i class="ki-duotone ki-calendar-remove fs-2 text-danger"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <span class="fs-4 fw-bold text-gray-800">{{ $stockStats['critical_expiring_items'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-md-4 col-xl-2">
        <div class="card card-flush bg-white shadow-sm border-0 rounded-3">
            <div class="card-body p-4 d-flex flex-column">
                <span class="text-gray-500 fw-bold fs-8 text-uppercase mb-1">Toplam Değer</span>
                <div class="d-flex align-items-center gap-2">
                    <i class="ki-duotone ki-dollar fs-2 text-success"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    <span class="fs-6 fw-bold text-gray-800">{{ number_format($stockStats['total_value'], 2) }} TL</span>
                </div>
            </div>
        </div>
    </div>
</div>
