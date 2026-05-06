<div class="app-table-container">
    <div class="pt-0">
        <div class="table-responsive">
            <table class="table align-middle fs-6 gy-4">
                <thead>
                    <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                        <th>Ürün Bilgisi</th>
                        <th>Klinik & Konum</th>
                        <th>Stok Durumu</th>
                        <th>Partiler</th>
                        <th class="text-end">İşlem</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 fw-semibold">
                    @forelse ($products as $product)
                        @php
                            $latestBatch = $product->batches->first();
                            $status = $product->stock_status;
                            $statusClass = $status === 'critical' ? 'danger' : ($status === 'low_stock' ? 'warning' : 'success');
                            $statusLabel = $status === 'critical' ? 'Kritik' : ($status === 'low_stock' ? 'Düşük' : 'Normal');
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="symbol symbol-40px symbol-circle bg-light-primary">
                                        <span class="symbol-label text-primary fw-bold">
                                            <i class="ki-duotone ki-package fs-2">
                                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                            </i>
                                        </span>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <a href="{{ route('products.show', $product->id) }}" class="text-gray-900 fw-bold text-hover-primary fs-6">{{ $product->name }}</a>
                                        <span class="text-muted fs-7">{{ $product->category ?: 'Kategori Yok' }} @if($product->sku) • {{ $product->sku }} @endif</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="badge badge-light-primary align-self-start">{{ $product->clinic?->name ?: ($latestBatch?->clinic?->name ?: 'Genel Stok') }}</span>
                                    <span class="text-muted fs-8 mt-1">{{ $latestBatch?->storage_location ?: 'Konum Yok' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column min-w-150px">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="text-gray-900 fw-bold">{{ $product->total_stock }} {{ $product->unit }}</span>
                                        <span class="badge badge-light-{{ $statusClass }} fs-8">{{ $statusLabel }}</span>
                                    </div>
                                    <div class="progress h-6px w-100 bg-light-{{ $statusClass }}">
                                        <div class="progress-bar bg-{{ $statusClass }}" role="progressbar" style="width: {{ min(100, ($product->total_stock / max(1, ($product->yellow_alert_level ?? 20) * 2)) * 100) }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="symbol-group symbol-hover">
                                    <div class="symbol symbol-30px symbol-circle" data-bs-toggle="tooltip" title="{{ $product->batches_count }} Parti">
                                        <span class="symbol-label bg-light-info text-info fs-8 fw-bold">{{ $product->batches_count }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ route('products.show', $product->id) }}" class="btn btn-sm btn-icon btn-light-primary" title="Detay">
                                        <i class="ki-duotone ki-eye fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-icon btn-light-info" data-stock-edit="{{ $product->id }}" title="Düzenle">
                                        <i class="ki-duotone ki-pencil fs-3"><span class="path1"></span><span class="path2"></span></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-icon btn-light-danger" data-stock-delete="{{ $product->id }}" title="Sil">
                                        <i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-10 text-muted">Stok kaydı bulunamadı.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <x-pager :paginator="$products" />
    </div>
</div>
