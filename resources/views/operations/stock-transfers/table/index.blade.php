<div class="card card-flush">
    <div class="card-header align-items-center py-5 gap-2 gap-md-5">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1">
                <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <input type="text" data-module-search class="form-control form-control-solid w-250px ps-12" placeholder="Transfer ara..." value="{{ request('search') }}" />
            </div>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('stock-transfers.create') }}" class="btn btn-primary" data-module-action="create">Yeni Transfer</a>
        </div>
    </div>
    <div class="card-body pt-0">
        <div class="table-responsive">
            <table class="table align-middle table-row-dashed fs-6 gy-5">
                <thead>
                    <tr class="text-start text-gray-400 fw-bold fs-7 text-uppercase gs-0">
                        <th class="min-w-100px">Urun</th>
                        <th class="min-w-100px">Kaynak</th>
                        <th class="min-w-100px">Hedef</th>
                        <th class="min-w-70px">Miktar</th>
                        <th class="min-w-100px">Durum</th>
                        <th class="min-w-100px">Tarih</th>
                        <th class="text-end min-w-70px">Islemler</th>
                    </tr>
                </thead>
                <tbody class="fw-semibold text-gray-600">
                    @forelse($transfers as $transfer)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="ms-5">
                                        <a href="{{ route('products.show', $transfer->product_id) }}" class="text-gray-800 text-hover-primary fs-5 fw-bold">{{ $transfer->product->name }}</a>
                                        <div class="text-muted fs-7">{{ $transfer->product->sku }}</div>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $transfer->fromClinic->name }}</td>
                            <td>{{ $transfer->toClinic->name }}</td>
                            <td>{{ $transfer->quantity }} {{ $transfer->product->unit }}</td>
                            <td>
                                <span class="badge badge-light-{{ $transfer->status_color }} fs-7 fw-bold">{{ $transfer->status_label }}</span>
                            </td>
                            <td>{{ $transfer->requested_at?->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <a href="#" class="btn btn-sm btn-light btn-active-light-primary" data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">Islemler 
                                <i class="ki-duotone ki-down fs-5 m-0"></i></a>
                                <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-125px py-4" data-kt-menu="true">
                                    @if($transfer->canApprove())
                                        <div class="menu-item px-3">
                                            <form action="{{ route('stock-transfers.approve', $transfer->id) }}" method="POST" data-module-form>
                                                @csrf
                                                <button type="submit" class="menu-link px-3 border-0 bg-transparent w-100">Onayla</button>
                                            </form>
                                        </div>
                                    @endif
                                    @if($transfer->canReject())
                                        <div class="menu-item px-3">
                                            <a href="#" class="menu-link px-3" data-module-action="reject" data-id="{{ $transfer->id }}">Reddet</a>
                                        </div>
                                    @endif
                                    @if($transfer->canCancel())
                                        <div class="menu-item px-3">
                                            <form action="{{ route('stock-transfers.cancel', $transfer->id) }}" method="POST" data-module-form>
                                                @csrf
                                                <button type="submit" class="menu-link px-3 border-0 bg-transparent w-100 text-danger">Iptal Et</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center">Kayit bulunamadi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="d-flex justify-content-between align-items-center mt-5">
            <div>{{ $transfers->links() }}</div>
        </div>
    </div>
</div>
