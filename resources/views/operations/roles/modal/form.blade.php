@if(in_array($modalMode, ['create', 'edit'], true))
    <div class="modal fade" id="roleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form method="POST" action="{{ $modalMode === 'edit' ? route('roles.update', $editingRole) : route('roles.store') }}">
                    @csrf
                    @if($modalMode === 'edit')
                        @method('PUT')
                    @endif
                    <div class="modal-header">
                        <h2>{{ $modalMode === 'edit' ? 'Rol Duzenle' : 'Yeni Rol' }}</h2>
                        <button type="button" class="btn btn-sm btn-icon btn-active-color-primary" data-bs-dismiss="modal">
                            <i class="ki-duotone ki-cross fs-1"></i>
                        </button>
                    </div>
                    <div class="modal-body py-10 px-lg-17">
                        <div class="mb-8">
                            <label class="form-label">Rol Adi</label>
                            <input class="form-control form-control-solid" name="name" value="{{ old('name', $editingRole?->name) }}" required />
                        </div>
                        <div class="row g-5">
                            @foreach($permissions as $permission)
                                <div class="col-md-6">
                                    <label class="form-check form-check-custom form-check-solid align-items-start">
                                        <input
                                            class="form-check-input mt-1"
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $permission->name }}"
                                            @checked(in_array($permission->name, old('permissions', $selectedPermissions ?? [])))
                                        />
                                        <span class="form-check-label">
                                            <span class="fw-bold text-gray-900">{{ $permission->name }}</span>
                                        </span>
                                    </label>
                                </div>
                            @endforeach
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
