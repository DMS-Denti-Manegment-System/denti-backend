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
                                <label class="form-label">Talep Eden Klinik</label>
                                <select class="form-select form-select-solid" name="requester_clinic_id" required>
                                    <option value="">Klinik secin</option>
                                    @foreach($clinics as $clinic)
                                        <option value="{{ $clinic->id }}" @selected((string) old('requester_clinic_id', auth()->user()->clinic_id) === (string) $clinic->id)>{{ $clinic->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Gonderen Klinik</label>
                                <select class="form-select form-select-solid" name="requested_from_clinic_id" required>
                                    <option value="">Klinik secin</option>
                                    @foreach($clinics as $clinic)
                                        <option value="{{ $clinic->id }}" @selected((string) old('requested_from_clinic_id') === (string) $clinic->id)>{{ $clinic->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Stok Kalemi</label>
                                <select class="form-select form-select-solid" name="stock_id" required>
                                    <option value="">Stok secin</option>
                                    @foreach($stocks as $stock)
                                        <option value="{{ $stock->id }}" @selected((string) old('stock_id') === (string) $stock->id)>{{ $stock->product?->name ?: 'Urun' }} / {{ $stock->clinic?->name ?: '-' }} / {{ $stock->current_stock }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Miktar</label>
                                <input type="number" min="1" class="form-control form-control-solid" name="requested_quantity" value="{{ old('requested_quantity', 1) }}" required />
                            </div>
                            <div class="col-12">
                                <label class="form-label">Talep Sebebi</label>
                                <textarea class="form-control form-control-solid" name="request_reason" rows="4">{{ old('request_reason') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Iptal</button>
                        <button type="submit" class="btn btn-primary">Olustur</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
