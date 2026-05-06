@extends('layouts.metronic')

@section('title', 'Sirketler - Denti')
@section('page-title', 'Sirket Yonetimi')
@section('page-subtitle', 'Super Admin gorunumu')

@section('content')
    <div class="app-module-shell">
        <div class="card card-flush app-module-table">
            @include('admin.companies.components.filters')
            @include('admin.companies.table.index')
        </div>
    </div>
@endsection

@push('modals')
    @include('admin.companies.modal.form')
@endpush

@push('scripts')
    @include('admin.companies.components.scripts')
@endpush
