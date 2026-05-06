<div class="card card-flush app-table-card app-module-table">
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th>Kategori</th>
                        <th>Renk</th>
                        <th>Todo</th>
                        <th>Durum</th>
                        <th>Islem</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @forelse($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td><span class="badge text-white" style="background: {{ $category->color ?: '#6c757d' }}">{{ $category->color ?: '#6c757d' }}</span></td>
                            <td>{{ $category->todos_count }}</td>
                            <td><span class="badge {{ $category->is_active ? 'badge-light-success' : 'badge-light-danger' }}">{{ $category->is_active ? 'Aktif' : 'Pasif' }}</span></td>
                            <td><a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-light-primary" data-module-edit>Duzenle</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-10 text-muted">Kategori kaydi bulunamadi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :paginator="$categories" />
    </div>
</div>
