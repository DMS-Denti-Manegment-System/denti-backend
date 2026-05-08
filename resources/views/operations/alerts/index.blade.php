@extends('layouts.app')
@section('title', 'Uyarilar - Denti')
@section('page-title', 'Uyari Merkezi')
@section('page-subtitle', 'Stok ve son kullanma alarmlari')
@section('content')
    <div id="alertsModule" class="app-module-shell" data-index-url="{{ route('alerts.index') }}">
        @include('operations.alerts.components.filters')
        <div id="alertsTableContainer" data-module-table>
            @include('operations.alerts.table.index')
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function () {
            window.DentiUI.createModule({
                name: 'alerts',
                root: '#alertsModule',
                indexUrl: @json(route('alerts.index')),
                actionFormSelector: '[data-module-action]',
            });
        });
    </script>
@endpush
