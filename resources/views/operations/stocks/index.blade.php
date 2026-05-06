@extends('layouts.metronic')

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
        <div class="modal-dialog modal-dialog-centered mw-650px" id="stockModalContent">
            <!-- AJAX content -->
        </div>
    </div>
@endpush

@push('scripts')
<script>
    $(document).ready(function() {
        window.DentiUI?.init(document);
        var $module = $('#stockModule');
        var $filterForm = $('#stockFilterForm');
        var $statsContainer = $('#stockStatsContainer');
        var $tableContainer = $('#stockTableContainer');
        var $modalElement = $('#stockModal');
        var $modalContent = $('#stockModalContent');
        var modalInstance = new bootstrap.Modal(document.getElementById('stockModal'));

        var currentUrl = $module.data('url');
        var currentModalState = null;

        window.StockPage = {
            reload: function() { loadData(false); },
            openCreate: function() { openModal('create'); },
            openEdit: function(id) { openModal('edit', id); },
            openDetail: function(id, tab) { openModal('detail', id, tab || 'history'); },
            openAdjust: function(id) { openModal('adjust', id); }
        };

        loadData();

        $filterForm.on('change', 'select', function() {
            loadData();
        });

        $filterForm.on('submit', function(e) {
            e.preventDefault();
            loadData();
        });

        var searchTimer;
        $filterForm.find('input[name="search"]').on('keyup', function() {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                loadData();
            }, 500);
        });

        $('#resetFilters').on('click', function() {
            $filterForm[0].reset();
            $filterForm.find('select').val(null).trigger('change');
            loadData();
        });

        $(document).on('click', '#stockTableContainer .pagination a', function(e) {
            e.preventDefault();
            currentUrl = $(this).attr('href');
            loadData(false);
        });

        function loadData(resetToDefaultUrl) {
            if (resetToDefaultUrl !== false) {
                currentUrl = $module.data('url');
            }

            var formData = $filterForm.serialize();
            var urlWithParams = currentUrl + (currentUrl.indexOf('?') !== -1 ? '&' : '?') + formData;

            $tableContainer.css('opacity', '0.5');

            $.ajax({
                url: urlWithParams,
                type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(response) {
                    $statsContainer.html(response.statsHtml);
                    $tableContainer.html(response.tableHtml);
                    window.DentiUI?.init($tableContainer[0]);
                    $tableContainer.css('opacity', '1');
                },
                error: function() {
                    window.DentiUI?.notify('error', 'Veriler yuklenirken bir hata olustu.');
                    $tableContainer.css('opacity', '1');
                }
            });
        }

        function openModal(mode, id, tab) {
            modalInstance.hide();
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css('overflow', '').css('padding-right', '');

            var baseUrl = $module.data('url');
            var url = baseUrl + (baseUrl.indexOf('?') !== -1 ? '&' : '?') + 'modal=' + mode;
            
            if (id && (mode === 'edit' || mode === 'detail' || mode === 'adjust')) {
                var param = (mode === 'edit') ? 'edit' : 'product';
                url += '&' + param + '=' + id;
            }
            if (tab) {
                url += '&tab=' + tab;
            }

            currentModalState = { mode: mode, id: id, tab: tab };
            
            $modalContent.html('<div class="modal-content"><div class="modal-body text-center py-20"><div class="spinner-border text-primary"></div></div></div>');
            modalInstance.show();

            $.ajax({
                url: url,
                type: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(response) {
                    $modalContent.html(response.modalHtml);
                    initModalComponents();
                    if (modalInstance.handleUpdate) modalInstance.handleUpdate(); 
                },
                error: function() {
                    modalInstance.hide();
                    window.DentiUI?.notify('error', 'Islem sirasinda bir hata olustu.');
                }
            });
        }

        function initModalComponents() {
            window.DentiUI?.init($modalElement[0]);

            var $expiryToggle = $('#expiry_toggle');
            var $expiryContainer = $('#expiry_date_container');
            if ($expiryToggle.length) {
                $expiryToggle.on('change', function() {
                    var isChecked = $(this).is(':checked');
                    $expiryContainer.toggleClass('d-none', !isChecked);
                    $expiryContainer.find('input[name="expiry_date"]').prop('required', isChecked);
                });
            }

            var $subUnitToggle = $('#sub_unit_toggle');
            var $subUnitContainer = $('#sub_unit_container');
            if ($subUnitToggle.length) {
                $subUnitToggle.on('change', function() {
                    var isChecked = $(this).is(':checked');
                    $subUnitContainer.toggleClass('d-none', !isChecked);
                    $subUnitContainer.find('input').prop('required', isChecked);
                });
            }
        }

        $(document).on('click', '#btnCreateStock', function() { openModal('create'); });
        $(document).on('click', '[data-stock-edit]', function() { openModal('edit', $(this).attr('data-stock-edit')); });
        $(document).on('click', '[data-stock-detail]', function() { openModal('detail', $(this).attr('data-stock-detail'), 'history'); });
        $(document).on('click', '[data-stock-adjust]', function() { openModal('adjust', $(this).attr('data-stock-adjust')); });
        $(document).on('click', '[data-stock-tab]', function(e) {
            e.preventDefault();
            openModal('detail', $(this).attr('data-stock-product'), $(this).attr('data-stock-tab'));
        });

        $(document).on('click', '#stockModalContent .pagination a', function(e) {
            e.preventDefault();
            if (currentModalState && currentModalState.mode === 'detail') {
                var href = $(this).attr('href');
                var tab = 'history';
                if (href.indexOf('tab=') !== -1) {
                    tab = href.split('tab=')[1].split('&')[0];
                }
                openModal('detail', currentModalState.id, tab);
            }
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
                            loadData(false);
                        }
                    });
                }
            });
        });

        $(document).on('submit', '#stockForm, #stockAdjustForm', function(e) {
            e.preventDefault();
            var $form = $(this);
            var $btn = $form.find('button[type="submit"]');
            var method = $form.find('input[name="_method"]').val() || 'POST';

            $btn.attr('data-kt-indicator', 'on').prop('disabled', true);

            $.ajax({
                url: $form.attr('action'),
                type: method,
                data: $form.serialize(),
                success: function(response) {
                    modalInstance.hide();
                    window.DentiUI?.notify('success', response.message || 'Basarili');
                    loadData(false);
                },
                error: function(xhr) {
                    $btn.removeAttr('data-kt-indicator').prop('disabled', false);
                    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                        window.DentiUI?.showValidationErrors(xhr.responseJSON.errors);
                    } else {
                        window.DentiUI?.notify('error', 'Bir hata olustu.');
                    }
                }
            });
        });
    });
</script>
@endpush
