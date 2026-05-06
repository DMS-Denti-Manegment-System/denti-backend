@extends('layouts.metronic')
@section('title', 'Profil - Denti')
@section('page-title', 'Profil')
@section('page-subtitle', 'Hesap ozeti')
@section('content')
    <div class="row g-5 g-xl-8">
        @include('operations.profile.components.info-form')
        @include('operations.profile.components.security-form')
    </div>
@endsection
