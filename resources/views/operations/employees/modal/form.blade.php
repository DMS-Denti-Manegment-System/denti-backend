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
                            <div class="col-12 mt-10">
                                <label class="form-label fw-bold fs-4 mb-5">Personel Yetkileri</label>
                                <div class="row g-7">
                                    @php
                                        $selectedPermissions = old('permission_names', $editingEmployee?->permissions?->pluck('name')->all() ?? []);
                                        $groups = [
                                            'Stok Yönetimi' => ['view-stocks', 'create-stocks', 'update-stocks', 'delete-stocks', 'adjust-stocks', 'use-stocks'],
                                            'Klinik Yönetimi' => ['view-clinics', 'create-clinics', 'update-clinics', 'delete-clinics'],
                                            'Raporlar & Analiz' => ['view-reports', 'export-reports', 'view-audit-logs'],
                                            'Sistem & Personel' => ['manage-users', 'manage-company'],
                                            'Görevler (Todo)' => ['view-todos', 'manage-todos'],
                                        ];
                                        $labels = [
                                            'view-stocks' => 'Stokları Görüntüle',
                                            'create-stocks' => 'Yeni Ürün/Stok Ekle',
                                            'update-stocks' => 'Stok Düzenle',
                                            'delete-stocks' => 'Stok Sil',
                                            'adjust-stocks' => 'Stok Düzeltme (+/-)',
                                            'use-stocks' => 'Stok Kullanımı',
                                            'view-clinics' => 'Klinikleri Görüntüle',
                                            'create-clinics' => 'Yeni Klinik Ekle',
                                            'update-clinics' => 'Klinik Düzenle',
                                            'delete-clinics' => 'Klinik Sil',
                                            'view-reports' => 'Raporları Görüntüle',
                                            'export-reports' => 'Rapor Dışa Aktar',
                                            'view-audit-logs' => 'İşlem Kayıtlarını Gör',
                                            'manage-users' => 'Personel Yönetimi',
                                            'manage-company' => 'Şirket Ayarları',
                                            'view-todos' => 'Görevleri Görüntüle',
                                            'manage-todos' => 'Görev Yönetimi',
                                        ];
                                    @endphp

                                    @foreach($groups as $groupName => $perms)
                                        <div class="col-md-6 col-lg-4">
                                            <div class="card card-flush border-dashed h-100">
                                                <div class="card-header pt-5">
                                                    <h3 class="card-title align-items-start flex-column">
                                                        <span class="card-label fw-bold text-gray-800 fs-6">{{ $groupName }}</span>
                                                    </h3>
                                                </div>
                                                <div class="card-body pt-0">
                                                    <div class="d-flex flex-column gap-3">
                                                        @foreach($perms as $permName)
                                                            <label class="form-check form-check-custom form-check-solid">
                                                                <input class="form-check-input h-20px w-20px" type="checkbox" name="permission_names[]" value="{{ $permName }}" @checked(in_array($permName, $selectedPermissions, true)) />
                                                                <span class="form-check-label fw-semibold text-gray-700">{{ $labels[$permName] ?? $permName }}</span>
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
                    <div class="modal-footer flex-center">
                        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Iptal</button>
                        <button type="submit" class="btn btn-primary">{{ $modalMode === 'edit' ? 'Guncelle' : 'Olustur' }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
