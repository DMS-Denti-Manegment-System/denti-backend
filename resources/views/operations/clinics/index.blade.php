@extends('layouts.metronic')
@section('title', 'Klinikler - Denti')
@section('page-title', 'Klinik Yonetimi')
@section('page-subtitle', 'Sube ve sorumlu listesi')
@section('content')
    <div class="app-module-shell">
        @include('operations.clinics.components.filters')
        @include('operations.clinics.table.index')
    </div>
@endsection

@push('modals')
    @include('operations.clinics.modal.form')
@endpush

@push('scripts')
    @include('operations.clinics.components.scripts')
@endpush
