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
                        <th class="min-w-200px">Ürün Bilgisi</th>
                        <th class="min-w-150px">Klinik & Konum</th>
                        <th class="min-w-150px">Stok Durumu</th>
                        <th class="min-w-80px text-center">Giriş Sayısı</th>
                        <th class="min-w-100px text-end">İşlemler</th>
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
                                <div class="d-flex align-items-center gap-3">
                                    <div class="app-stock-table__avatar shadow-sm">
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
                                <div class="app-stock-level">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="text-gray-900 fw-bold fs-7">{{ $product->total_stock }} adet</span>
                                        <span class="text-{{ $statusClass }} fw-bold fs-8">{{ $statusLabel }}</span>
                                    </div>
                                    <div class="progress h-5px w-100 bg-light rounded-pill">
                                        <div class="progress-bar bg-{{ $statusClass }} rounded-pill" role="progressbar" style="width: {{ $progressWidth }}%"></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="app-stock-table__counter shadow-sm">{{ $product->batches_count }}</span>
                            </td>
                             <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-sm btn-icon btn-light-primary" data-stock-edit="{{ $product->id }}" title="Düzenle">
                                        <i class="ki-duotone ki-pencil fs-3"><span class="path1"></span><span class="path2"></span></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-icon btn-light-danger" data-stock-delete="{{ $product->id }}" title="Sil">
                                        <i class="ki-duotone ki-trash fs-3"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span><span class="path5"></span></i>
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

        <div class="px-6 pb-6">
            <x-pager :paginator="$products" />
        </div>
    </div>
</div>
