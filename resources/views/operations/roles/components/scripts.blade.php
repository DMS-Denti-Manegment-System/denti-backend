<script>
    document.getElementById('roleSearch')?.addEventListener('input', function (event) {
        const query = event.target.value.toLowerCase();
        document.querySelectorAll('#roleTable tbody tr').forEach((row) => {
            row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
        });
    });
</script>
@if(in_array($modalMode, ['create', 'edit'], true))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('roleModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            window.DentiUI?.init(modalEl);
            modal.show();
            modalEl.addEventListener('hidden.bs.modal', function () {
                window.location.href = @json(route('roles.index'));
            }, { once: true });
        }
    });
</script>
@endif
