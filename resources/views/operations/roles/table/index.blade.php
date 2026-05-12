<div class="card card-flush app-table-card app-module-table">
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="roleTable">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th>Rol</th>
                        <th>Kapsam</th>
                        <th>Izin Sayisi</th>
                        <th>Ilk Izinler</th>
                        <th>Islem</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @forelse ($roles as $role)
                        <tr>
                            <td>{{ $role->name }}</td>
                            <td>
                                <span class="badge badge-light-primary">
                                    Sistem
                                </span>
                            </td>
                            <td>{{ $role->permissions->count() }}</td>
                            <td>{{ $role->permissions->pluck('name')->take(4)->implode(', ') ?: '-' }}</td>
                            <td>
                                <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-light-primary" data-module-edit>Düzenle</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-10 text-muted">Rol kaydi bulunamadi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-9 pb-9 text-muted fs-7">
            Toplam {{ $roles->count() }} rol listeleniyor.
        </div>
    </div>
</div>
