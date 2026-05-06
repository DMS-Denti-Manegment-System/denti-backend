<div class="card card-flush app-table-card app-module-table">
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th>Baslik</th>
                        <th>Kategori</th>
                        <th>Durum</th>
                        <th>Tarih</th>
                        <th>Islem</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @forelse($todos as $todo)
                        <tr>
                            <td><div>{{ $todo->title }}</div><div class="text-muted fs-7">{{ $todo->description ?: '-' }}</div></td>
                            <td>{{ $todo->category?->name ?: '-' }}</td>
                            <td><span class="badge {{ $todo->completed ? 'badge-light-success' : 'badge-light-warning' }}">{{ $todo->completed ? 'Tamamlandi' : 'Bekliyor' }}</span></td>
                            <td>{{ optional($todo->created_at)->format('d.m.Y') }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('todos.edit', $todo) }}" class="btn btn-sm btn-light-primary" data-module-edit>Duzenle</a>
                                    <form method="POST" action="{{ route('todos.toggle', $todo) }}" data-module-action>
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-light-warning">{{ $todo->completed ? 'Ac' : 'Bitir' }}</button>
                                    </form>
                                    @unless($todo->completed)
                                        <form method="POST" action="{{ route('todos.destroy', $todo) }}" data-module-action>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger">Sil</button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-10 text-muted">Todo kaydi bulunamadi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :paginator="$todos" />
    </div>
</div>
