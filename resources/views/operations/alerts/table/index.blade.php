<div class="card card-flush app-table-card app-module-table">
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5" id="alertsTable">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th>Önem</th>
                        <th>Başlık</th>
                        <th>Klinik</th>
                        <th>Ürün</th>
                        <th>Tarih</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @forelse($alerts as $alert)
                        <tr>
                            <td>
                                @php
                                    $severityLabel = [
                                        'critical' => ['danger', 'Kritik'],
                                        'high' => ['warning', 'Yüksek'],
                                        'normal' => ['primary', 'Normal'],
                                        'info' => ['info', 'Bilgi'],
                                    ][$alert->severity] ?? ['info', $alert->severity];
                                @endphp
                                <span class="badge badge-light-{{ $severityLabel[0] }}">{{ $severityLabel[1] }}</span>
                            </td>
                            <td>
                                <div>{{ $alert->title }}</div>
                                <div class="text-muted fs-7">{{ $alert->message }}</div>
                            </td>
                            <td>{{ $alert->clinic?->name ?: '-' }}</td>
                            <td>{{ $alert->product?->name ?: '-' }}</td>
                            <td>{{ optional($alert->created_at)->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-muted">Uyarı kaydı bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :paginator="$alerts" />
    </div>
</div>
