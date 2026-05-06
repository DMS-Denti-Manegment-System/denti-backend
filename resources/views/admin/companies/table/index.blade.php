<div class="card-body pt-0">
    <div class="table-responsive">
        <table class="table align-middle table-row-dashed fs-6 gy-5 app-data-table" id="companyTable">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th>Sirket</th>
                    <th>Domain</th>
                    <th>Plan</th>
                    <th>Kullanici</th>
                    <th>Durum</th>
                    <th>Kayit</th>
                    <th>Islem</th>
                </tr>
            </thead>
            <tbody class="text-gray-600 fw-semibold">
                @foreach ($companies as $company)
                    <tr>
                        <td>
                            <div class="d-flex flex-column">
                                <span class="text-gray-900 fw-bold">{{ $company->name }}</span>
                                <span class="text-muted fs-7">{{ $company->code }}</span>
                            </div>
                        </td>
                        <td>{{ $company->domain }}.denti.com</td>
                        <td><span class="badge badge-light-info text-uppercase">{{ $company->subscription_plan }}</span></td>
                        <td>{{ $company->users_count }}</td>
                        <td>
                            <span class="badge {{ $company->status === 'active' ? 'badge-light-success' : 'badge-light-danger' }}">
                                {{ $company->status === 'active' ? 'Aktif' : 'Pasif' }}
                            </span>
                        </td>
                        <td>{{ optional($company->created_at)->format('d.m.Y') }}</td>
                        <td><a href="{{ route('admin.companies.edit', $company) }}" class="btn btn-sm btn-light-primary" data-module-edit>Duzenle</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
