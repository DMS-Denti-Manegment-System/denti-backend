@extends('layouts.metronic')
@section('title', 'Roller - Denti')
@section('page-title', 'Rol ve Yetki Yonetimi')
@section('page-subtitle', 'Sirket ve sistem rolleri')
@section('content')
    <div class="app-module-shell">
        @include('operations.roles.components.filters')
        @include('operations.roles.table.index')
    </div>
@endsection

@push('modals')
    @include('operations.roles.modal.form')
@endpush

@push('scripts')
    @include('operations.roles.components.scripts')
@endpush
