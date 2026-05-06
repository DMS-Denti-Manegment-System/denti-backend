<div class="row g-5 g-xl-8">
    @foreach (['Urunler' => $summary['products'], 'Tedarikciler' => $summary['suppliers'], 'Klinikler' => $summary['clinics'], 'Aktif Uyarilar' => $summary['alerts'], 'Bekleyen Talepler' => $summary['pending_requests'], 'Acik Todo' => $summary['open_todos']] as $label => $value)
        <div class="col-md-6 col-xl-4">
            <div class="card card-flush h-md-100">
                <div class="card-body">
                    <div class="fs-7 text-muted">{{ $label }}</div>
                    <div class="fs-2hx fw-bold">{{ $value }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>
