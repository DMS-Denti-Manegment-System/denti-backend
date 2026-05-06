<div class="app-stock-table-card">
    <div class="app-stock-table-card__header">
        <div class="app-stock-table-card__title">Stok Listesi</div>
        <div class="app-stock-table-card__meta">Toplam {{ $products->total() }} kayıt</div>
    </div>
    <div class="pt-0">
        <div class="table-responsive">
            <table class="table align-middle fs-6 gy-4 app-stock-table">
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
                            $statusLabel = $status === 'critical' ? 'Kritik Seviye' : ($status === 'low_stock' ? 'Düşük Seviye' : 'Normal');
                            $progressWidth = $status === 'critical' ? 20 : ($status === 'low_stock' ? 45 : 100);
                        @endphp
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-3 min-w-225px">
                                    <div class="app-stock-table__avatar">
                                        <i class="ki-duotone ki-package fs-2 text-primary">
                                            <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                        </i>
                                    </div>
                                    <div class="d-flex flex-column">
                                        <a href="{{ route('products.show', $product->id) }}" class="text-gray-900 fw-bold text-hover-primary fs-6">{{ $product->name }}</a>
                                        <span class="text-muted fs-7">{{ $product->category ?: 'Kategori Yok' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="badge badge-light-primary align-self-start">{{ $product->clinic?->name ?: ($latestBatch?->clinic?->name ?: 'Genel Stok') }}</span>
                                    <span class="text-muted fs-8 mt-1">{{ $latestBatch?->storage_location ?: 'Konum bilgisi yok' }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="app-stock-level min-w-225px">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-gray-900 fw-bold">{{ $product->total_stock }} adet</span>
                                        <span class="badge badge-light-{{ $statusClass }} fs-8">{{ $statusLabel }}</span>
                                    </div>
                                    <div class="progress h-6px w-100 bg-light">
                                        <div class="progress-bar bg-{{ $statusClass }}" role="progressbar" style="width: {{ $progressWidth }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="app-stock-table__counter">{{ $product->batches_count }}</span>
                            </td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end align-items-center gap-2">
                                    <button type="button" class="btn btn-sm btn-light-primary px-4" data-stock-detail="{{ $product->id }}">Yönet</button>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-icon btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="ki-duotone ki-dots-vertical fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route('products.show', $product->id) }}">Detay</a></li>
                                            <li><button class="dropdown-item" type="button" data-stock-edit="{{ $product->id }}">Düzenle</button></li>
                                            <li><button class="dropdown-item" type="button" data-stock-adjust="{{ $product->id }}">Stok Hareketi</button></li>
                                            <li><button class="dropdown-item text-danger" type="button" data-stock-delete="{{ $product->id }}">Sil</button></li>
                                        </ul>
                                    </div>
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

        <div class="px-6 pb-6">
            <x-pager :paginator="$products" />
        </div>
    </div>
</div>
