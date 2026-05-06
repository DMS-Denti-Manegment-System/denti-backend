@if ($paginator->hasPages() || $paginator->total() > 10)
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-4 mt-8">
        <div class="d-flex align-items-center gap-4">
            <div class="text-muted fs-7 fw-semibold">
                Toplam <span class="text-gray-800">{{ $paginator->total() }}</span> kayıt • Sayfa <span class="text-gray-800">{{ $paginator->currentPage() }}</span> / {{ $paginator->lastPage() }}
            </div>
            
            <div class="d-flex align-items-center gap-2 ms-4">
                <span class="text-muted fs-8">Göster:</span>
                <select name="per_page" class="form-select form-select-sm form-select-solid w-75px" data-control="per-page-selector">
                    @foreach([10, 20, 50, 100] as $count)
                        <option value="{{ $count }}" {{ $paginator->perPage() == $count ? 'selected' : '' }}>{{ $count }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="app-pager-shell shadow-sm rounded">
            {{ $paginator->appends(request()->except('page'))->onEachSide(1)->links() }}
        </div>
    </div>
@endif
