@extends('layouts.app')

@section('title', 'Stok Yönetimi - Denti')
@section('page-title', 'Stok Yönetimi')
@section('page-subtitle', 'Ürün ve miktar görünümü')

@section('content')
    <div id="stockModule" data-url="{{ url()->full() }}">
        <!-- Stats Container -->
        <div id="stockStatsContainer" class="mb-8">
            @include('operations.stocks.components.stats')
        </div>

        <!-- Filters -->
        @include('operations.stocks.components.filters')

        <!-- Table Container -->
        <div id="stockTableContainer">
            @include('operations.stocks.table.index')
        </div>
    </div>
@endsection

@push('modals')
    <!-- Modal Container -->
    <div class="modal fade" id="stockModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-900px" id="stockModalContent">
            <!-- AJAX content -->
        </div>
    </div>
    <template id="stockCreateModalTemplate">
        @php($modalMode = 'create')
        @include('operations.stocks.modal.form')
    </template>
@endpush

@push('scripts')
<script>
    $(function () {
        window.StockModule = window.DentiUI.createModule({
            name: 'stocks',
            root: '#stockModule',
            indexUrl: @json(url()->full()),
            tableContainer: '#stockTableContainer',
            filterForm: '#stockFilterForm',
            modalHost: '#stockModalContent',
            createSelector: '[data-module-create-remote]',
            initialLoad: false,
            onAfterLoad: function(response) {
                if (response.statsHtml) {
                    $('#stockStatsContainer').html(response.statsHtml);
                }
            },
            onModalLoaded: function($modal) {
                // SKT Toggle
                $modal.find('#expiry_toggle').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('#expiry_date_container').removeClass('d-none');
                    } else {
                        $('#expiry_date_container').addClass('d-none');
                    }
                });

                // Alt Birim Toggle
                $modal.find('#sub_unit_toggle').on('change', function() {
                    if ($(this).is(':checked')) {
                        $('#sub_unit_container').removeClass('d-none');
                    } else {
                        $('#sub_unit_container').addClass('d-none');
                    }
                });
            }
        });

        $('#stockQuickCreate').on('click', function () {
            const tpl = document.getElementById('stockCreateModalTemplate');
            if (!tpl) return;
            $('#stockModalContent').html(tpl.innerHTML);
            window.DentiUI?.init(document.getElementById('stockModalContent'));
            bootstrap.Modal.getOrCreateInstance(document.getElementById('stockModal')).show();
        });

        // Ürün detay sayfasındaki manuel tetikleyiciler için (Gerekirse)
        $(document).on('click', '[data-stock-detail]', function() { 
            const id = $(this).attr('data-stock-detail');
            window.location.href = '/stock/products/' + id;
        });

        $(document).on('click', '[data-stock-delete]', function() {
            var id = $(this).attr('data-stock-delete');
            Swal.fire({
                title: 'Emin misiniz?',
                text: "Bu ürün ve ilgili tüm stok kayıtları silinecektir!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Evet, sil!',
                cancelButtonText: 'İptal',
                customClass: { confirmButton: 'btn btn-danger', cancelButton: 'btn btn-light' }
            }).then(function(result) {
                if (result.isConfirmed) {
                    $.ajax({
                        url: '/stocks/' + id,
                        type: 'DELETE',
                        data: { _token: '{{ csrf_token() }}' },
                        success: function(response) {
                            window.DentiUI?.notify('success', response.message);
                            window.StockModule.reload(false);
                        }
                    });
                }
            });
        });
    });
</script>
@endpush
