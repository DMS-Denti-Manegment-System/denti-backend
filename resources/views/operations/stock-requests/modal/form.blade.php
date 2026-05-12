@if($modalMode === 'create')
    <div class="modal fade" id="stockRequestModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST" action="{{ route('stock-requests.store') }}">
                    @csrf
                    <div class="modal-header">
                        <h2>Yeni Stok Talebi</h2>
                        <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body py-10 px-lg-17">
                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label required">Talep Eden Klinik</label>
                                <select class="form-select form-select-solid" name="requester_clinic_id" required>
                                    <option value="">Klinik seçin</option>
                                    @foreach($clinics as $clinic)
                                        <option value="{{ $clinic->id }}" @selected((string) old('requester_clinic_id', auth()->user()->clinic_id) === (string) $clinic->id)>{{ $clinic->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Gönderen Klinik</label>
                                <select class="form-select form-select-solid" name="requested_from_clinic_id" required>
                                    <option value="">Klinik seçin</option>
                                    @foreach($clinics as $clinic)
                                        <option value="{{ $clinic->id }}" @selected((string) old('requested_from_clinic_id') === (string) $clinic->id)>{{ $clinic->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label required">Stok Kalemi (Ürün)</label>
                                <select class="form-select form-select-solid" name="stock_id" id="stockRequestSelectAjax" data-placeholder="Ürün veya SKU ile stok arayın..." required>
                                    @if(old('stock_id'))
                                        <option value="{{ old('stock_id') }}" selected>Seçili Stok ({{ old('stock_id') }})</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label required">Miktar</label>
                                <input type="number" min="1" class="form-control form-control-solid" name="requested_quantity" value="{{ old('requested_quantity', 1) }}" required />
                            </div>
                            <div class="col-12">
                                <label class="form-label">Talep Sebebi</label>
                                <textarea class="form-control form-control-solid" name="request_reason" rows="4">{{ old('request_reason') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" class="btn btn-primary">Talep Oluştur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            var initSelect2 = function() {
                var $el = $('#stockRequestSelectAjax');
                var $fromClinic = $('select[name="requested_from_clinic_id"]');
                
                if (!$el.length || $el.hasClass('select2-hidden-accessible')) return;

                $el.select2({
                    dropdownParent: $('#stockRequestModal'),
                    placeholder: $el.data('placeholder'),
                    allowClear: true,
                    minimumInputLength: 0, // 0 yaptık ki tıklayınca da gelsin
                    width: '100%',
                    ajax: {
                        url: '{{ route("stocks.search-ajax") }}',
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                search: params.term,
                                page: params.page || 1,
                                status: 'active',
                                clinic_id: $fromClinic.val() // Sadece seçilen klinikteki stokları getir
                            };
                        },
                        processResults: function(data, params) {
                            params.page = params.page || 1;
                            
                            // DentiUI API Success format handles:
                            // 1. { success: true, data: { data: [...], meta: ... } }
                            // 2. { success: true, data: [...] }
                            // 3. [...] (direct array)
                            
                            var items = [];
                            if (data && data.data) {
                                if (Array.isArray(data.data)) {
                                    items = data.data;
                                } else if (data.data.data && Array.isArray(data.data.data)) {
                                    items = data.data.data;
                                }
                            } else if (Array.isArray(data)) {
                                items = data;
                            }
                            
                            return {
                                results: $.map(items, function(item) {
                                    var productName = item.product_name || (item.product ? item.product.name : (item.name || 'Bilinmeyen Ürün'));
                                    var clinicName = item.clinic_name || (item.clinic ? item.clinic.name : 'Genel');
                                    return {
                                        id: item.id,
                                        text: productName + ' [' + clinicName + '] - Mevcut: ' + (item.available_stock || 0)
                                    };
                                }),
                                pagination: {
                                    more: (data && data.meta) ? (data.meta.current_page < data.meta.last_page) : false
                                }
                            };
                        },
                        cache: true
                    }
                });
            };

            // Gönderen klinik değiştiğinde stok aramasını sıfırla
            $(document).on('change', 'select[name="requested_from_clinic_id"]', function() {
                $('#stockRequestSelectAjax').val(null).trigger('change');
            });

            initSelect2();
            
            $('#stockRequestModal').on('shown.bs.modal', function() {
                initSelect2();
            });
        });
    </script>
@endif

@if($modalMode === 'detail' && isset($requestItem))
    <div class="modal fade" id="stockRequestDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Talep Detayı: {{ $requestItem->request_number }}</h2>
                    <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="d-flex flex-column gap-5">
                        <div class="d-flex flex-stack">
                            <span class="fw-bold text-gray-800">Ürün:</span>
                            <span class="text-gray-600 text-end">{{ $requestItem->stock?->product?->name }}</span>
                        </div>
                        <div class="d-flex flex-stack">
                            <span class="fw-bold text-gray-800">Miktar:</span>
                            <span class="text-gray-600 text-end">{{ $requestItem->requested_quantity }} {{ $requestItem->stock?->unit }}</span>
                        </div>
                        <div class="d-flex flex-stack">
                            <span class="fw-bold text-gray-800">Talep Eden:</span>
                            <span class="text-gray-600 text-end">{{ $requestItem->requesterClinic?->name }}</span>
                        </div>
                        <div class="d-flex flex-stack">
                            <span class="fw-bold text-gray-800">Gönderen:</span>
                            <span class="text-gray-600 text-end">{{ $requestItem->requestedFromClinic?->name }}</span>
                        </div>
                        <div class="d-flex flex-stack">
                            <span class="fw-bold text-gray-800">Durum:</span>
                            <span class="badge badge-light-{{ $requestItem->status === 'completed' ? 'success' : ($requestItem->status === 'rejected' ? 'danger' : 'warning') }}">
                                {{ strtoupper($requestItem->status) }}
                            </span>
                        </div>
                        @if($requestItem->request_reason)
                            <div class="separator separator-dashed my-2"></div>
                            <div class="d-flex flex-column">
                                <span class="fw-bold text-gray-800 mb-1">Talep Sebebi:</span>
                                <p class="text-gray-600 bg-light p-3 rounded">{{ $requestItem->request_reason }}</p>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Kapat</button>
                </div>
            </div>
        </div>
    </div>
@endif
