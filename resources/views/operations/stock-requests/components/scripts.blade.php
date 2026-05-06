@if($modalMode === 'create')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('stockRequestModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            window.DentiUI?.init(modalEl);
            modal.show();
            modalEl.addEventListener('hidden.bs.modal', function () {
                window.location.href = @json(route('stock-requests.index'));
            }, { once: true });
        }
    });
</script>
@endif
