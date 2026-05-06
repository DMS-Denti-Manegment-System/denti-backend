@extends('layouts.metronic')
@section('title', 'Todo - Denti')
@section('page-title', 'Todo Listesi')
@section('page-subtitle', 'Takip ve yapilacaklar')
@section('content')
    <div class="app-module-shell">
        @include('operations.todos.components.filters')
        @include('operations.todos.table.index')
    </div>
@endsection

@push('modals')
    @include('operations.todos.modal.form')
@endpush

@push('scripts')
    @include('operations.todos.components.scripts')
@endpush
