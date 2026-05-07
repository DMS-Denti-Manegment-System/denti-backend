@extends('layouts.app')
@section('title', 'Stok Transferleri - Denti')
@section('page-title', 'Stok Transferleri')
@section('page-subtitle', 'Klinikler arasi stok tasima')
@section('content')
    <div id="stockTransfersModule" class="app-module-shell" data-index-url="{{ route('stock-transfers.index') }}">
        <div id="stockTransfersTableContainer" data-module-table>
            @include('operations.stock-transfers.table.index')
        </div>
    </div>
@endsection

@push('modals')
    <div id="stockTransferModalHost">
        @include('operations.stock-transfers.modal.form')
    </div>
@endpush

@push('scripts')
    <script>
        $(function () {
            window.StockTransferModule = window.DentiUI.createModule({
                name: 'stock-transfers',
                root: '#stockTransfersModule',
                indexUrl: @json(route('stock-transfers.index')),
                modalHost: '#stockTransferModalHost',
            });
        });
    </script>
@endpush
