@extends('layouts.app')
@section('title', 'Roller - Denti')
@section('page-title', 'Rol ve Yetki Yonetimi')
@section('page-subtitle', 'Sirket ve sistem rolleri')
@section('content')
    <div id="rolesModule" class="app-module-shell" data-index-url="{{ route('roles.index') }}">
        @include('operations.roles.components.filters')
        <div id="rolesTableContainer" data-module-table>
            @include('operations.roles.table.index')
        </div>
    </div>
@endsection

@push('modals')
    <div id="roleModalHost">
        @include('operations.roles.modal.form')
    </div>
@endpush

@push('scripts')
    <script>
        $(function () {
            window.RoleModule = window.DentiUI.createModule({
                name: 'roles',
                root: '#rolesModule',
                indexUrl: @json(route('roles.index')),
                modalHost: '#roleModalHost',
            });
        });
    </script>
@endpush
