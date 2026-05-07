<div class="card card-flush app-table-card app-module-table">
    <div class="card-body pt-0">
        <div id="alertsBulkActions" class="d-none align-items-center mb-5 p-5 bg-light-primary rounded">
            <span class="me-5 fw-bold"><span id="selectedCount">0</span> adet seçildi</span>
            <button type="button" class="btn btn-sm btn-success me-2" id="bulkResolveBtn">Toplu Çöz</button>
            <button type="button" class="btn btn-sm btn-warning me-2" id="bulkDismissBtn">Toplu Yoksay</button>
            <button type="button" class="btn btn-sm btn-danger" id="bulkDeleteBtn">Toplu Sil</button>
        </div>
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="alertsTable">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th class="w-10px pe-2">
                            <div class="form-check form-check-sm form-check-custom form-check-solid me-3">
                                <input class="form-check-input" type="checkbox" data-kt-check="true" data-kt-check-target="#alertsTable .form-check-input" value="1" />
                            </div>
                        </th>
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
                            <td>
                                <div class="form-check form-check-sm form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" value="{{ $alert->id }}" />
                                </div>
                            </td>
                            <td><span class="badge {{ $alert->severity === 'critical' ? 'badge-light-danger' : ($alert->severity === 'high' ? 'badge-light-warning' : 'badge-light-info') }}">{{ $alert->severity }}</span></td>
                            <td><div>{{ $alert->title }}</div><div class="text-muted fs-7">{{ $alert->message }}</div></td>
                            <td>{{ $alert->clinic?->name ?: '-' }}</td>
                            <td>{{ $alert->product?->name ?: '-' }}</td>
                            <td>{{ optional($alert->created_at)->format('d.m.Y H:i') }}</td>
                            <td>
                                <div class="d-flex gap-2">
                                    @if(!$alert->is_resolved)
                                        <form method="POST" action="{{ route('alerts.resolve', $alert) }}" data-module-action>
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-light-success">Coz</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('alerts.dismiss', $alert) }}" data-module-action>
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-light-danger">Kapat</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-muted">Uyari kaydi bulunamadi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :paginator="$alerts" />
    </div>
</div>
