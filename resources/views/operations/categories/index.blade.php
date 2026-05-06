@extends('layouts.app')
@section('title', 'Kategoriler - Denti')
@section('page-title', 'Kategori Yonetimi')
@section('page-subtitle', 'Todo kategorileri')
@section('content')
    <div id="categoriesModule" class="app-module-shell" data-index-url="{{ route('categories.index') }}">
        @include('operations.categories.components.filters')
        <div id="categoriesTableContainer" data-module-table>
            @include('operations.categories.table.index')
        </div>
    </div>
@endsection

@push('modals')
    <div id="categoryModalHost">
        @include('operations.categories.modal.form')
    </div>
@endpush

@push('scripts')
    <script>
        $(function () {
            window.CategoryModule = window.DentiUI.createModule({
                name: 'categories',
                root: '#categoriesModule',
                indexUrl: @json(route('categories.index')),
                modalHost: '#categoryModalHost',
            });
        });
    </script>
@endpush
