@if(in_array($modalMode, ['create', 'edit'], true))
    <div class="modal fade" id="companyModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST" action="{{ $modalMode === 'edit' ? route('admin.companies.update', $editingCompany) : route('admin.companies.store') }}">
                    @csrf
                    @if($modalMode === 'edit')
                        @method('PUT')
                    @endif
                    <div class="modal-header">
                        <h2>{{ $modalMode === 'edit' ? 'Sirket Duzenle' : 'Yeni Sirket' }}</h2>
                        <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body py-10 px-lg-17">
                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label">Sirket Adi</label>
                                <input class="form-control form-control-solid" name="name" value="{{ old('name', $editingCompany?->name) }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Klinik Kodu</label>
                                <input class="form-control form-control-solid" name="code" value="{{ old('code', $editingCompany?->code) }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Domain</label>
                                <input class="form-control form-control-solid" name="domain" value="{{ old('domain', $editingCompany?->domain) }}" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Plan</label>
                                <select class="form-select form-select-solid" name="subscription_plan" required>
                                    @foreach(['basic' => 'Basic', 'standard' => 'Standard', 'premium' => 'Premium'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('subscription_plan', $editingCompany?->subscription_plan ?? 'basic') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Max Kullanici</label>
                                <input type="number" min="1" class="form-control form-control-solid" name="max_users" value="{{ old('max_users', $editingCompany?->max_users ?? 5) }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Durum</label>
                                <select class="form-select form-select-solid" name="status" required>
                                    @foreach(['active' => 'Aktif', 'inactive' => 'Pasif', 'suspended' => 'Askida'] as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $editingCompany?->status ?? 'active') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @if($modalMode === 'create')
                            <div class="separator separator-dashed my-10"></div>
                            <div class="row g-5">
                                <div class="col-md-4">
                                    <label class="form-label">Ad Soyad</label>
                                    <input class="form-control form-control-solid" name="owner_name" value="{{ old('owner_name') }}" required />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Kullanici Adi</label>
                                    <input class="form-control form-control-solid" name="owner_username" value="{{ old('owner_username') }}" required />
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">E-posta</label>
                                    <input type="email" class="form-control form-control-solid" name="owner_email" value="{{ old('owner_email') }}" />
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Iptal</button>
                        <button type="submit" class="btn btn-primary">{{ $modalMode === 'edit' ? 'Guncelle' : 'Olustur' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
