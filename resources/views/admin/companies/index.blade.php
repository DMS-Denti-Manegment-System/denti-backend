@extends('layouts.app')

@section('title', 'Sirketler - Denti')
@section('page-title', 'Sirket Yonetimi')
@section('page-subtitle', 'Super Admin gorunumu')

@section('content')
    <div id="companiesModule" class="app-module-shell" data-index-url="{{ route('admin.companies') }}">
        <div class="card card-flush app-module-table">
            @include('admin.companies.components.filters')
            <div id="companiesTableContainer" data-module-table>
                @include('admin.companies.table.index')
            </div>
        </div>
    </div>
@endsection

@push('modals')
    <div id="companyModalHost">
        @include('admin.companies.modal.form')
    </div>
@endpush

@push('scripts')
    <script>
        $(function () {
            window.CompanyModule = window.DentiUI.createModule({
                name: 'companies',
                root: '#companiesModule',
                indexUrl: @json(route('admin.companies')),
                modalHost: '#companyModalHost',
            });
        });
    </script>
@endpush
