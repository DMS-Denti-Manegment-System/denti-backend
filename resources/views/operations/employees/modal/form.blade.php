@if(in_array($modalMode, ['create', 'edit'], true))
    <div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST" action="{{ $modalMode === 'edit' ? route('employees.update', $editingEmployee) : route('employees.store') }}">
                    @csrf
                    @if($modalMode === 'edit')
                        @method('PUT')
                    @endif
                    <div class="modal-header">
                        <h2>{{ $modalMode === 'edit' ? 'Personel Duzenle' : 'Yeni Personel' }}</h2>
                        <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body py-10 px-lg-17">
                        <div class="row g-5">
                            <div class="col-md-6">
                                <label class="form-label">Ad Soyad</label>
                                <input class="form-control form-control-solid" name="name" value="{{ old('name', $editingEmployee?->name) }}" required />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">E-posta</label>
                                <input class="form-control form-control-solid" name="email" value="{{ old('email', $editingEmployee?->email) }}" />
                            </div>
                            @if($modalMode === 'create')
                                <div class="col-md-6">
                                    <label class="form-label">Kullanici Adi</label>
                                    <input class="form-control form-control-solid" name="username" value="{{ old('username') }}" required />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Sifre</label>
                                    <input type="password" class="form-control form-control-solid" name="password" required />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Sifre Tekrar</label>
                                    <input type="password" class="form-control form-control-solid" name="password_confirmation" required />
                                </div>
                            @else
                                <div class="col-md-6">
                                    <label class="form-label">Yeni Sifre</label>
                                    <input type="password" class="form-control form-control-solid" name="password" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Yeni Sifre Tekrar</label>
                                    <input type="password" class="form-control form-control-solid" name="password_confirmation" />
                                </div>
                            @endif
                            <div class="col-md-6">
                                <label class="form-label">Klinik</label>
                                <select class="form-select form-select-solid" name="clinic_id">
                                    <option value="">Klinik secin</option>
                                    @foreach($clinics as $clinic)
                                        <option value="{{ $clinic->id }}" @selected((string) old('clinic_id', $editingEmployee?->clinic_id) === (string) $clinic->id)>{{ $clinic->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-center">
                                <label class="form-check form-check-custom form-check-solid mt-8">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $editingEmployee?->is_active ?? true)) />
                                    <span class="form-check-label">Aktif</span>
                                </label>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Roller</label>
                                <div class="row g-3">
                                    @php($selectedRoles = old('role_names', $editingEmployee?->roles?->pluck('name')->all() ?? []))
                                    @foreach($roles as $role)
                                        <div class="col-md-4">
                                            <label class="form-check form-check-custom form-check-solid">
                                                <input class="form-check-input" type="checkbox" name="role_names[]" value="{{ $role->name }}" @checked(in_array($role->name, $selectedRoles, true)) />
                                                <span class="form-check-label">{{ $role->name }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
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
