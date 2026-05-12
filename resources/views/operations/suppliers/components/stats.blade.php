<div id="supplierStatsContainer" class="row g-5 mb-6">
    <div class="col-md-4">
        <div class="card card-flush h-100">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-gray-500 fw-semibold fs-6">Toplam Tedarikçi</div>
                    <div class="fs-2hx fw-bold text-gray-900">{{ $supplierStats['total'] ?? 0 }}</div>
                </div>
                <div class="symbol symbol-50px">
                    <div class="symbol-label bg-light-primary">
                        <i class="ki-duotone ki-truck fs-2x text-primary"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-flush h-100">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-gray-500 fw-semibold fs-6">Aktif Tedarikçiler</div>
                    <div class="fs-2hx fw-bold text-success">{{ $supplierStats['active'] ?? 0 }}</div>
                </div>
                <div class="symbol symbol-50px">
                    <div class="symbol-label bg-light-success">
                        <i class="ki-duotone ki-check-circle fs-2x text-success"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-flush h-100">
            <div class="card-body d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-gray-500 fw-semibold fs-6">Pasif Tedarikçiler</div>
                    <div class="fs-2hx fw-bold text-danger">{{ $supplierStats['passive'] ?? 0 }}</div>
                </div>
                <div class="symbol symbol-50px">
                    <div class="symbol-label bg-light-danger">
                        <i class="ki-duotone ki-cross-circle fs-2x text-danger"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
