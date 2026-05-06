@extends('layouts.app')
@section('title', 'Personel - Denti')
@section('page-title', 'Personel Yonetimi')
@section('page-subtitle', 'Kullanici, rol ve klinik bilgileri')
@section('content')
    <div id="employeesModule" class="app-module-shell" data-index-url="{{ route('employees.index') }}">
        @include('operations.employees.components.filters')
        <div id="employeesTableContainer" data-module-table>
            @include('operations.employees.table.index')
        </div>
    </div>
@endsection

@push('modals')
    <div id="employeeModalHost">
        @include('operations.employees.modal.form')
    </div>
@endpush

@push('scripts')
    <script>
        $(function () {
            window.EmployeeModule = window.DentiUI.createModule({
                name: 'employees',
                root: '#employeesModule',
                indexUrl: @json(route('employees.index')),
                modalHost: '#employeeModalHost',
            });
        });
    </script>
@endpush
