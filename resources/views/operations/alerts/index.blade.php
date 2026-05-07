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

            function handleBulkAction(url, confirmMsg) {
                const ids = table.find('tbody .form-check-input:checked').map(function() {
                    return $(this).val();
                }).get();

                if (ids.length === 0) return;

                Swal.fire({
                    text: confirmMsg,
                    icon: "warning",
                    showCancelButton: true,
                    buttonsStyling: false,
                    confirmButtonText: "Evet",
                    cancelButtonText: "Hayır",
                    customClass: {
                        confirmButton: "btn fw-bold btn-danger",
                        cancelButton: "btn fw-bold btn-active-light-primary"
                    }
                }).then(function (result) {
                    if (result.value) {
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: { _token: '{{ csrf_token() }}', ids: ids },
                            success: function(res) {
                                module.refreshTable();
                                Swal.fire({ text: res.message, icon: "success", buttonsStyling: false, confirmButtonText: "Tamam", customClass: { confirmButton: "btn fw-bold btn-primary" } });
                            },
                            error: function(err) {
                                Swal.fire({ text: "Bir hata olustu.", icon: "error", buttonsStyling: false, confirmButtonText: "Tamam", customClass: { confirmButton: "btn fw-bold btn-primary" } });
                            }
                        });
                    }
                });
            }

            $('#bulkResolveBtn').on('click', () => handleBulkAction('{{ route('alerts.bulk-resolve') }}', "Secili uyarilari cozmek istediginize emin misiniz?"));
            $('#bulkDismissBtn').on('click', () => handleBulkAction('{{ route('alerts.bulk-dismiss') }}', "Secili uyarilari yoksaymak istediginize emin misiniz?"));
            $('#bulkDeleteBtn').on('click', () => handleBulkAction('{{ route('alerts.bulk-delete') }}', "Secili uyarilari silmek istediginize emin misiniz?"));
        });
    </script>
@endpush
