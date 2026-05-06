<div class="card card-flush app-table-card app-module-table">
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th>Firma</th>
                        <th>Iletisim</th>
                        <th>Telefon</th>
                        <th>E-posta</th>
                        <th>Durum</th>
                        <th>Islem</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @forelse($suppliers as $supplier)
                        <tr>
                            <td>{{ $supplier->name }}</td>
                            <td>{{ $supplier->contact_person ?: '-' }}</td>
                            <td>{{ $supplier->phone ?: '-' }}</td>
                            <td>{{ $supplier->email ?: '-' }}</td>
                            <td><span class="badge {{ $supplier->is_active ? 'badge-light-success' : 'badge-light-danger' }}">{{ $supplier->is_active ? 'Aktif' : 'Pasif' }}</span></td>
                            <td><a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-light-primary" data-module-edit>Duzenle</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-muted">Tedarikci kaydi bulunamadi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :paginator="$suppliers" />
    </div>
</div>
