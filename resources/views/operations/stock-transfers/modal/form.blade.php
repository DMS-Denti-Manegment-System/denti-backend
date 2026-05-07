<div class="modal fade" id="stockTransferModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-650px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">Yeni Transfer</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>
            <div class="modal-body py-10 px-lg-17">
                <form action="{{ route('stock-transfers.store') }}" method="POST" id="stockTransferForm">
                    @csrf
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Stok Secimi</label>
                        <select name="stock_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Stok secin...">
                            <option></option>
                            @foreach($stocks as $stock)
                                <option value="{{ $stock->id }}">{{ $stock->product->name }} - {{ $stock->clinic->name }} (Mevcut: {{ $stock->current_stock }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Hedef Klinik</label>
                        <select name="to_clinic_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Klinik secin...">
                            <option></option>
                            @foreach($clinics as $clinic)
                                <option value="{{ $clinic->id }}">{{ $clinic->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Miktar</label>
                        <input type="number" class="form-control form-control-solid" name="quantity" min="1" />
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fs-6 fw-semibold mb-2">Notlar</label>
                        <textarea class="form-control form-control-solid" name="notes" rows="3"></textarea>
                    </div>
                    <div class="text-center pt-15">
                        <button type="reset" class="btn btn-light me-3" data-bs-dismiss="modal">Iptal</button>
                        <button type="submit" class="btn btn-primary">
                            <span class="indicator-label">Kaydet</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
