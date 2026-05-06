<div class="card card-flush app-table-card app-module-table">
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th>Kullanici</th>
                        <th>Klinik</th>
                        <th>Roller</th>
                        <th>Durum</th>
                        <th>Islem</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @forelse($users as $employee)
                        <tr>
                            <td><div>{{ $employee->name }}</div><div class="text-muted fs-7">{{ $employee->username ?: $employee->email }}</div></td>
                            <td>{{ $employee->clinic?->name ?: '-' }}</td>
                            <td>{{ $employee->roles->pluck('name')->implode(', ') ?: '-' }}</td>
                            <td><span class="badge {{ $employee->is_active ? 'badge-light-success' : 'badge-light-danger' }}">{{ $employee->is_active ? 'Aktif' : 'Pasif' }}</span></td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('employees.edit', $employee) }}" class="btn btn-sm btn-light-primary">Duzenle</a>
                                    @if($employee->id !== auth()->id())
                                        <form method="POST" action="{{ route('employees.destroy', $employee) }}" onsubmit="return confirm('Personel silinsin mi?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger">Sil</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-10 text-muted">Personel kaydi bulunamadi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :paginator="$users" />
    </div>
</div>
