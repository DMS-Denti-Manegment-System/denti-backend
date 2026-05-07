@extends('layouts.app')
@section('title', 'Tedarikciler - Denti')
@section('page-title', 'Tedarikci Yonetimi')
@section('page-subtitle', 'Tedarik agi listesi')
@section('content')
    <div id="suppliersModule" class="app-module-shell" data-index-url="{{ route('suppliers.index') }}">
        <div class="row g-5 mb-6">
            <div class="col-md-4"><div class="card card-flush"><div class="card-body"><div class="text-muted">Toplam Tedarikci</div><div class="fs-2 fw-bold">{{ $supplierStats['total'] ?? 0 }}</div></div></div></div>
            <div class="col-md-4"><div class="card card-flush"><div class="card-body"><div class="text-muted">Aktif</div><div class="fs-2 fw-bold text-success">{{ $supplierStats['active'] ?? 0 }}</div></div></div></div>
            <div class="col-md-4"><div class="card card-flush"><div class="card-body"><div class="text-muted">Pasif</div><div class="fs-2 fw-bold text-danger">{{ $supplierStats['passive'] ?? 0 }}</div></div></div></div>
        </div>
        @include('operations.suppliers.components.filters')
        <div id="suppliersTableContainer" data-module-table>
            @include('operations.suppliers.table.index')
        </div>
    </div>
@endsection

@push('modals')
    <div id="supplierModalHost">
        @include('operations.suppliers.modal.form')
    </div>
@endpush

@push('scripts')
    <script>
        $(function () {
            window.SupplierModule = window.DentiUI.createModule({
                name: 'suppliers',
                root: '#suppliersModule',
                indexUrl: @json(route('suppliers.index')),
                modalHost: '#supplierModalHost',
            });
        });
    </script>
@endpush
