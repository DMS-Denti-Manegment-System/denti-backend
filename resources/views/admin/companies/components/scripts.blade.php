<script>
    document.getElementById('companySearch')?.addEventListener('input', function (event) {
        const query = event.target.value.toLowerCase();
        document.querySelectorAll('#companyTable tbody tr').forEach((row) => {
            row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
        });
    });
</script>
@if(in_array($modalMode, ['create', 'edit'], true))
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('companyModal');
        if (modalEl) {
            const modal = new bootstrap.Modal(modalEl);
            window.DentiUI?.init(modalEl);
            modal.show();
            modalEl.addEventListener('hidden.bs.modal', function () {
                window.location.href = @json(route('admin.companies'));
            }, { once: true });
        }
    });
</script>
@endif
