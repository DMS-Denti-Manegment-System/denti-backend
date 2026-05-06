@extends('layouts.app')

@section('title', 'Stok Yönetimi - Denti')
@section('page-title', 'Stok Yönetimi')
@section('page-subtitle', 'Ürün ve miktar görünümü')

@section('content')
    <div id="stockModule" data-url="{{ route('stocks.index') }}">
        <!-- Stats Container -->
        <div id="stockStatsContainer" class="mb-8">
            <div class="d-flex justify-content-center py-10">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>

        <!-- Filters -->
        @include('operations.stocks.components.filters')

        <!-- Table Container -->
        <div id="stockTableContainer">
             <!-- AJAX content -->
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
@endpush

@push('scripts')
<script>
    $(function () {
        window.StockModule = window.DentiUI.createModule({
            name: 'stocks',
            root: '#stockModule',
            indexUrl: @json(route('stocks.index')),
            tableContainer: '#stockTableContainer',
            filterForm: '#stockFilterForm',
            modalHost: '#stockModalContent',
            onAfterLoad: function(response) {
                if (response.statsHtml) {
                    $('#stockStatsContainer').html(response.statsHtml);
                }
            }
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
