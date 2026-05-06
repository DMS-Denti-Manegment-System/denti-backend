<div class="card card-flush mb-6 app-toolbar-card app-module-toolbar">
    <div class="card-body py-5">
        <form method="GET" class="row g-4 align-items-end">
            {{ $slot }}
            <div class="col-md-3 col-lg-2">
                <button type="submit" class="btn btn-primary w-100">Filtrele</button>
            </div>
            <div class="col-md-3 col-lg-2">
                <a href="{{ url()->current() }}" class="btn btn-light w-100">Temizle</a>
            </div>
        </form>
    </div>
</div>
