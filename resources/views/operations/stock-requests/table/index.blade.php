<div class="card card-flush app-table-card app-module-table">
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th>Talep</th>
                        <th>Urun</th>
                        <th>Akis</th>
                        <th>Miktar</th>
                        <th>Durum</th>
                        <th>Islem</th>
                    </tr>
                </thead>
                <tbody class="text-gray-600 fw-semibold">
                    @forelse($requests as $requestItem)
                        <tr>
                            <td><div>{{ $requestItem->request_number ?: '#' . $requestItem->id }}</div><div class="text-muted fs-7">{{ $requestItem->requested_by }}</div></td>
                            <td>{{ $requestItem->stock?->product?->name ?: $requestItem->stock?->name ?: '-' }}</td>
                            <td>{{ $requestItem->requesterClinic?->name ?: '-' }} → {{ $requestItem->requestedFromClinic?->name ?: '-' }}</td>
                            <td>{{ $requestItem->requested_quantity }}</td>
                            <td><span class="badge badge-light">{{ $requestItem->status }}</span></td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    @if($requestItem->status === 'pending')
                                        <form method="POST" action="{{ route('stock-requests.approve', $requestItem) }}" class="d-flex gap-2">
                                            @csrf
                                            <input type="hidden" name="approved_quantity" value="{{ $requestItem->requested_quantity }}" />
                                            <button type="submit" class="btn btn-sm btn-light-success">Onayla</button>
                                        </form>
                                        <form method="POST" action="{{ route('stock-requests.reject', $requestItem) }}">
                                            @csrf
                                            <input type="hidden" name="rejection_reason" value="Web panel uzerinden reddedildi." />
                                            <button type="submit" class="btn btn-sm btn-light-danger">Reddet</button>
                                        </form>
                                    @elseif($requestItem->status === 'approved')
                                        <form method="POST" action="{{ route('stock-requests.ship', $requestItem) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-light-primary">Sevk Et</button>
                                        </form>
                                    @elseif($requestItem->status === 'in_transit')
                                        <form method="POST" action="{{ route('stock-requests.complete', $requestItem) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-light-success">Tamamla</button>
                                        </form>
                                    @else
                                        <span class="text-muted fs-8">Islem yok</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-10 text-muted">Talep kaydi bulunamadi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-pager :paginator="$requests" />
    </div>
</div>
