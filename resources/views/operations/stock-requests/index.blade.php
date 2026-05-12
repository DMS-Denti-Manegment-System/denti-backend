@extends('layouts.app')
@section('title', 'Stok Talepleri - Denti')
@section('page-title', 'Stok Talepleri')
@section('page-subtitle', 'Klinikler arasi talep akisi')
@section('content')
    <div id="stockRequestsModule" class="app-module-shell" data-index-url="{{ route('stock-requests.index') }}">
        <div id="stockRequestStatsContainer">
            @include('operations.stock-requests.components.stats')
        </div>
        @include('operations.stock-requests.components.filters')
        <div id="stockRequestsTableContainer" data-module-table>
            @include('operations.stock-requests.table.index')
        </div>
    </div>
@endsection

@push('modals')
    <div id="stockRequestModalHost">
        @include('operations.stock-requests.modal.form')
    </div>
@endpush

@push('scripts')
    <script>
        $(function () {
            window.StockRequestModule = window.DentiUI.createModule({
                name: 'stock-requests',
                root: '#stockRequestsModule',
                indexUrl: @json(route('stock-requests.index')),
                modalHost: '#stockRequestModalHost',
                onAfterLoad: function(response) {
                    if (response.statsHtml) {
                        $('#stockRequestStatsContainer').html(response.statsHtml);
                    }
                }
            });
        });
    </script>
@endpush
