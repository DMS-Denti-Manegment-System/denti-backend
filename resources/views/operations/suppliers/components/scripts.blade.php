@if(in_array($modalMode, ['create', 'edit'], true))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('supplierModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            window.DentiUI?.init(modalEl);
            modal.show();
            modalEl.addEventListener('hidden.bs.modal', function () {
                window.location.href = @json(route('suppliers.index'));
            }, { once: true });
        }
    });
</script>
@endif
