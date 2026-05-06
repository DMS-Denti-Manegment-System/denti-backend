@if(in_array($modalMode, ['create', 'edit'], true))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('categoryModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            window.DentiUI?.init(modalEl);
            modal.show();
            modalEl.addEventListener('hidden.bs.modal', function () {
                window.location.href = @json(route('categories.index'));
            }, { once: true });
        }
    });
</script>
@endif
