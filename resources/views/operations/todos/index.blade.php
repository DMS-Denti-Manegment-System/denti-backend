@extends('layouts.app')
@section('title', 'Todo - Denti')
@section('page-title', 'Todo Listesi')
@section('page-subtitle', 'Takip ve yapilacaklar')
@section('content')
    <div id="todosModule" class="app-module-shell" data-index-url="{{ route('todos.index') }}">
        @include('operations.todos.components.filters')
        <div id="todosTableContainer" data-module-table>
            @include('operations.todos.table.index')
        </div>
    </div>
@endsection

@push('modals')
    <div id="todoModalHost">
        @include('operations.todos.modal.form')
    </div>
@endpush

@push('scripts')
    <script>
        $(function () {
            window.TodoModule = window.DentiUI.createModule({
                name: 'todos',
                root: '#todosModule',
                indexUrl: @json(route('todos.index')),
                modalHost: '#todoModalHost',
            });
        });
    </script>
@endpush
