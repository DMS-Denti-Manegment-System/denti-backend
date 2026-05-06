<div class="card card-flush app-table-card app-module-table">
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th>Onem</th>
                        <th>Baslik</th>
                        <th>Klinik</th>
                        <th>Urun</th>
                        <th>Tarih</th>
                        <th>Islem</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @forelse($alerts as $alert)
                        <tr>
                            <td><span class="badge {{ $alert->severity === 'critical' ? 'badge-light-danger' : ($alert->severity === 'high' ? 'badge-light-warning' : 'badge-light-info') }}">{{ $alert->severity }}</span></td>
                            <td><div>{{ $alert->title }}</div><div class="text-muted fs-7">{{ $alert->message }}</div></td>
                            <td>{{ $alert->clinic?->name ?: '-' }}</td>
                            <td>{{ $alert->product?->name ?: '-' }}</td>
                            <td>{{ optional($alert->created_at)->format('d.m.Y H:i') }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    @if(!$alert->is_resolved)
                                        <form method="POST" action="{{ route('alerts.resolve', $alert) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-light-success">Coz</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('alerts.dismiss', $alert) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-light-danger">Kapat</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-muted">Uyari kaydi bulunamadi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :paginator="$alerts" />
    </div>
</div>
