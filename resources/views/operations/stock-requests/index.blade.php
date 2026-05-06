@extends('layouts.metronic')
@section('title', 'Stok Talepleri - Denti')
@section('page-title', 'Stok Talepleri')
@section('page-subtitle', 'Klinikler arasi talep akisi')
@section('content')
    <div class="app-module-shell">
        @include('operations.stock-requests.components.filters')
        @include('operations.stock-requests.table.index')
    </div>
@endsection

@push('modals')
    @include('operations.stock-requests.modal.form')
@endpush

@push('scripts')
    @include('operations.stock-requests.components.scripts')
@endpush
