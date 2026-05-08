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
                    @forelse($alerts as $row)
                        <tr>
                            <td>
                                @php
                                    $severityLabel = [
                                        'critical' => ['danger', 'Kritik'],
                                        'high' => ['warning', 'Yüksek'],
                                        'medium' => ['primary', 'Orta'],
                                        'info' => ['info', 'Bilgi'],
                                    ][$row['severity'] ?? 'info'] ?? ['info', $row['severity'] ?? 'Bilgi'];
                                @endphp
                                <span class="badge badge-light-{{ $severityLabel[0] }}">{{ $severityLabel[1] }}</span>
                            </td>
                            <td>
                                <div>{{ $row['title'] ?? '-' }}</div>
                                <div class="text-muted fs-7">{{ $row['message'] ?? '-' }}</div>
                            </td>
                            <td>{{ $row['clinic_name'] ?? '-' }}</td>
                            <td>{{ $row['product_name'] ?? '-' }}</td>
                            <td>{{ $row['created_at_label'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-10 text-muted">Uyarı kaydı bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :paginator="$alerts" />
    </div>
</div>
