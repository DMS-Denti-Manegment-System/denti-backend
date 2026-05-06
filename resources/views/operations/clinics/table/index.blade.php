<div class="card card-flush app-table-card app-module-table">
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th>Klinik</th>
                        <th>Sorumlu</th>
                        <th>Sehir</th>
                        <th>Telefon</th>
                        <th>Durum</th>
                        <th>Islem</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @forelse($clinics as $clinic)
                        <tr>
                            <td>{{ $clinic->name }}</td>
                            <td>{{ $clinic->responsible_person ?: '-' }}</td>
                            <td>{{ $clinic->city ?: '-' }}</td>
                            <td>{{ $clinic->phone ?: '-' }}</td>
                            <td><span class="badge {{ $clinic->is_active ? 'badge-light-success' : 'badge-light-danger' }}">{{ $clinic->is_active ? 'Aktif' : 'Pasif' }}</span></td>
                            <td><a href="{{ route('clinics.edit', $clinic) }}" class="btn btn-sm btn-light-primary" data-module-edit>Duzenle</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-muted">Klinik kaydi bulunamadi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :paginator="$clinics" />
    </div>
</div>
