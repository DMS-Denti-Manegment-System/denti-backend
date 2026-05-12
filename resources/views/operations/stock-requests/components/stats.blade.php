@php
    $stats = $stats ?? ['pending' => 0, 'approved' => 0, 'in_transit' => 0, 'completed' => 0, 'rejected' => 0, 'total' => 0];
@endphp
<div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    <div class="col">
        <div class="card card-flush h-md-100">
            <div class="card-header pt-5">
                <div class="card-title d-flex flex-column">
                    <div class="d-flex align-items-center">
                        <span class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ $stats['pending'] ?? 0 }}</span>
                        <i class="ki-duotone ki-timer fs-1 text-warning"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    </div>
                    <span class="text-gray-500 pt-1 fw-semibold fs-6">Bekleyen</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card card-flush h-md-100">
            <div class="card-header pt-5">
                <div class="card-title d-flex flex-column">
                    <div class="d-flex align-items-center">
                        <span class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ $stats['approved'] ?? 0 }}</span>
                        <i class="ki-duotone ki-check-circle fs-1 text-primary"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <span class="text-gray-500 pt-1 fw-semibold fs-6">Onaylanan</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card card-flush h-md-100">
            <div class="card-header pt-5">
                <div class="card-title d-flex flex-column">
                    <div class="d-flex align-items-center">
                        <span class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ $stats['in_transit'] ?? 0 }}</span>
                        <i class="ki-duotone ki-delivery-3 fs-1 text-info"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                    </div>
                    <span class="text-gray-500 pt-1 fw-semibold fs-6">Transfer</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card card-flush h-md-100">
            <div class="card-header pt-5">
                <div class="card-title d-flex flex-column">
                    <div class="d-flex align-items-center">
                        <span class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ $stats['rejected'] ?? 0 }}</span>
                        <i class="ki-duotone ki-cross-circle fs-1 text-danger"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <span class="text-gray-500 pt-1 fw-semibold fs-6">Reddedilen</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card card-flush h-md-100">
            <div class="card-header pt-5">
                <div class="card-title d-flex flex-column">
                    <div class="d-flex align-items-center">
                        <span class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ $stats['completed'] ?? 0 }}</span>
                        <i class="ki-duotone ki-double-check fs-1 text-success"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                    <span class="text-gray-500 pt-1 fw-semibold fs-6">Tamamlanan</span>
                </div>
            </div>
        </div>
    </div>
</div>
