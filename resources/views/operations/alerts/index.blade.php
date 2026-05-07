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
            const module = window.DentiUI.createModule({
                name: 'alerts',
                root: '#alertsModule',
                indexUrl: @json(route('alerts.index')),
                actionFormSelector: '[data-module-action]',
            });

            // Bulk Actions Logic
            const table = $('#alertsTable');
            const bulkActions = $('#alertsBulkActions');
            const selectedCount = $('#selectedCount');
            const masterCheck = table.find('[data-kt-check="true"]');

            function updateBulkActions() {
                const checked = table.find('tbody .form-check-input:checked');
                if (checked.length > 0) {
                    bulkActions.removeClass('d-none').addClass('d-flex');
                    selectedCount.text(checked.length);
                } else {
                    bulkActions.addClass('d-none').removeClass('d-flex');
                }
            }

            table.on('change', '.form-check-input', function() {
                setTimeout(updateBulkActions, 50);
            });

            table.on('change', '.form-check-input', function() {
                setTimeout(updateBulkActions, 50);
            });
        });
    </script>
@endpush
