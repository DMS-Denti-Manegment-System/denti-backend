@if(in_array($modalMode, ['create', 'edit'], true))
    @php
        $isEditMode = $modalMode === 'edit' && $editingEmployee;
    @endphp
    <div class="modal fade" id="employeeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST" action="{{ $isEditMode ? route('employees.update', $editingEmployee) : route('employees.store') }}">
                    @csrf
                    @if($isEditMode)
                        @method('PUT')
                    @endif
                    <div class="modal-header">
                        <h2>{{ $isEditMode ? 'Personel Duzenle' : 'Yeni Personel' }}</h2>
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
                            @if(!$isEditMode)
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
                            <div class="col-12 mt-10">
                                @php
                                    $selectedPermissions = old('permission_names', $editingEmployee?->permissions?->pluck('name')->all() ?? []);
                                    $hasPerm = static fn (?string $permission): bool => is_string($permission) && $permission !== '';
                                @endphp
                                <label class="form-label fw-bold fs-4 mb-5">Personel Yetki Matrisi</label>
                                <div class="table-responsive border rounded">
                                    <table class="table align-middle table-row-bordered mb-0">
                                        <thead class="bg-light">
                                            <tr class="fw-bold text-gray-700 text-uppercase fs-8">
                                                <th class="min-w-200px">Modül</th>
                                                <th class="text-center min-w-120px">Göster</th>
                                                <th class="text-center min-w-120px">Ekle</th>
                                                <th class="text-center min-w-120px">Güncelle</th>
                                                <th class="text-center min-w-120px">Sil</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($permissionCrudMatrix as $row)
                                                <tr>
                                                    <td class="fw-semibold text-gray-800">{{ $row['module'] }}</td>
                                                    @foreach(['show', 'create', 'update', 'delete'] as $action)
                                                        @php $permName = $row['permissions'][$action] ?? null; @endphp
                                                        <td class="text-center">
                                                            @if($hasPerm($permName))
                                                                <label class="form-check form-check-custom form-check-solid justify-content-center mb-0">
                                                                    <input
                                                                        class="form-check-input h-20px w-20px"
                                                                        type="checkbox"
                                                                        name="permission_names[]"
                                                                        value="{{ $permName }}"
                                                                        @checked(in_array($permName, $selectedPermissions, true))
                                                                    />
                                                                </label>
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    @endforeach
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-8">
                                    <div class="fw-bold fs-6 mb-3">Modül Özellikleri</div>
                                    <div class="row g-6">
                                        @foreach($permissionFeatureGroups as $groupName => $groupPermissions)
                                            <div class="col-lg-6">
                                                <div class="card card-flush border-dashed h-100">
                                                    <div class="card-header py-5">
                                                        <h3 class="card-title fw-bold fs-6">{{ $groupName }}</h3>
                                                    </div>
                                                    <div class="card-body pt-1">
                                                        <div class="d-flex flex-column gap-3">
                                                            @foreach($groupPermissions as $permName)
                                                                <label class="form-check form-check-custom form-check-solid">
                                                                    <input
                                                                        class="form-check-input h-20px w-20px"
                                                                        type="checkbox"
                                                                        name="permission_names[]"
                                                                        value="{{ $permName }}"
                                                                        @checked(in_array($permName, $selectedPermissions, true))
                                                                    />
                                                                    <span class="form-check-label fw-semibold text-gray-700">
                                                                        {{ \App\Support\PermissionCatalog::label($permName) }}
                                                                    </span>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Iptal</button>
                        <button type="submit" class="btn btn-primary">{{ $isEditMode ? 'Guncelle' : 'Olustur' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
