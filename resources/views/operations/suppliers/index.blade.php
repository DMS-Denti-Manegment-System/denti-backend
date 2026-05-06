@extends('layouts.metronic')
@section('title', 'Tedarikciler - Denti')
@section('page-title', 'Tedarikci Yonetimi')
@section('page-subtitle', 'Tedarik agi listesi')
@section('content')
    <div class="app-module-shell">
        @include('operations.suppliers.components.filters')
        @include('operations.suppliers.table.index')
    </div>
@endsection

@push('modals')
    @include('operations.suppliers.modal.form')
@endpush

@push('scripts')
    @include('operations.suppliers.components.scripts')
@endpush
