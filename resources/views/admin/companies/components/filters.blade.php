<div class="card-header align-items-center py-5 gap-2 gap-md-5">
    <div class="card-title">
        <div class="d-flex align-items-center position-relative my-1">
            <i class="ki-duotone ki-magnifier fs-3 position-absolute ms-4">
                <span class="path1"></span><span class="path2"></span>
            </i>
            <input type="text" class="form-control form-control-solid w-250px ps-12" id="companySearch" placeholder="Sirket ara" />
        </div>
    </div>
    <div class="card-toolbar">
        <a href="{{ route('admin.companies.create') }}" class="btn btn-primary me-3">Yeni Sirket</a>
        <span class="badge badge-light-primary">{{ $companies->count() }} sirket</span>
    </div>
</div>
