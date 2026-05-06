@extends('layouts.metronic')
@section('title', 'Personel - Denti')
@section('page-title', 'Personel Yonetimi')
@section('page-subtitle', 'Kullanici, rol ve klinik bilgileri')
@section('content')
    <div class="app-module-shell">
        @include('operations.employees.components.filters')
        @include('operations.employees.table.index')
    </div>
@endsection

@push('modals')
    @include('operations.employees.modal.form')
@endpush

@push('scripts')
    @include('operations.employees.components.scripts')
@endpush
