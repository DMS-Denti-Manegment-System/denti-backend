@extends('layouts.app')
@section('title', 'Tedarikciler - Denti')
@section('page-title', 'Tedarikci Yonetimi')
@section('page-subtitle', 'Tedarik agi listesi')
@section('content')
    <div id="suppliersModule" class="app-module-shell" data-index-url="{{ route('suppliers.index') }}">
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
