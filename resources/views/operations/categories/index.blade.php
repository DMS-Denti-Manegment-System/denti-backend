@extends('layouts.metronic')
@section('title', 'Kategoriler - Denti')
@section('page-title', 'Kategori Yonetimi')
@section('page-subtitle', 'Todo kategorileri')
@section('content')
    <div class="app-module-shell">
        @include('operations.categories.components.filters')
        @include('operations.categories.table.index')
    </div>
@endsection

@push('modals')
    @include('operations.categories.modal.form')
@endpush

@push('scripts')
    @include('operations.categories.components.scripts')
@endpush
