@extends('layouts.app')
@section('title', 'Klinikler - Denti')
@section('page-title', 'Klinik Yonetimi')
@section('page-subtitle', 'Sube ve sorumlu listesi')
@section('content')
    <div id="clinicsModule" class="app-module-shell" data-index-url="{{ route('clinics.index') }}">
        <div class="row g-5 mb-6">
            <div class="col-md-4"><div class="card card-flush"><div class="card-body"><div class="text-muted">Toplam Klinik</div><div class="fs-2 fw-bold">{{ $clinicStats['total'] ?? 0 }}</div></div></div></div>
            <div class="col-md-4"><div class="card card-flush"><div class="card-body"><div class="text-muted">Aktif</div><div class="fs-2 fw-bold text-success">{{ $clinicStats['active'] ?? 0 }}</div></div></div></div>
            <div class="col-md-4"><div class="card card-flush"><div class="card-body"><div class="text-muted">Pasif</div><div class="fs-2 fw-bold text-danger">{{ $clinicStats['passive'] ?? 0 }}</div></div></div></div>
        </div>
        @include('operations.clinics.components.filters')
        <div id="clinicsTableContainer" data-module-table>
            @include('operations.clinics.table.index')
        </div>
    </div>
@endsection

@push('modals')
    <div id="clinicModalHost">
        @include('operations.clinics.modal.form')
    </div>
@endpush

@push('scripts')
    <script>
        $(function () {
            window.ClinicModule = window.DentiUI.createModule({
                name: 'clinics',
                root: '#clinicsModule',
                indexUrl: @json(route('clinics.index')),
                modalHost: '#clinicModalHost',
            });
        });
    </script>
@endpush
