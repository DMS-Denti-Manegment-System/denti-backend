@if ($paginator->hasPages())
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-4 mt-8">
        <div class="text-muted fs-7">
            Toplam {{ $paginator->total() }} kayit • Sayfa {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
        </div>
        <div class="app-pager-shell">
            {{ $paginator->onEachSide(1)->links() }}
        </div>
    </div>
@endif
