@extends('layouts.app')
@section('title', 'Klinikler - Denti')
@section('page-title', 'Klinik Yonetimi')
@section('page-subtitle', 'Sube ve sorumlu listesi')
@section('content')
    <div id="clinicsModule" class="app-module-shell" data-index-url="{{ route('clinics.index') }}">
        <div id="clinicStatsWrapper">
            @include('operations.clinics.components.stats')
        </div>
        @include('operations.clinics.components.filters')
        <div id="clinicsTableContainer" data-module-table>
            @include('operations.clinics.table.index')
        </div>
    </div>
@endsection

@push('modals')
    <div id="clinicModalHost">
        @include('operations.clinics.modal.form')
    </div>
@endpush

@push('scripts')
    <script>
        $(function () {
            window.ClinicModule = window.DentiUI.createModule({
                name: 'clinics',
                root: '#clinicsModule',
                indexUrl: @json(route('clinics.index')),
                modalHost: '#clinicModalHost',
            });
        });
    </script>
@endpush
