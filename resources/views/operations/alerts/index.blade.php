@extends('layouts.metronic')
@section('title', 'Uyarilar - Denti')
@section('page-title', 'Uyari Merkezi')
@section('page-subtitle', 'Stok ve son kullanma alarmlari')
@section('content')
    <div class="app-module-shell">
        @include('operations.alerts.components.filters')
        @include('operations.alerts.table.index')
    </div>
@endsection
