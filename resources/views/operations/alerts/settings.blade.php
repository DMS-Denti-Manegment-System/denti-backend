@extends('layouts.app')
@section('title', 'Uyarı Ayarları - Denti')
@section('page-title', 'Uyarı Ayarları')
@section('page-subtitle', 'Sistem geneli stok uyari limitleri')
@section('content')
    <div class="card card-flush">
        <div class="card-header"><h3 class="card-title">Genel Uyari Yapilandirmasi</h3></div>
        <div class="card-body">
            <form action="{{ route('alerts.update-settings') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row g-9 mb-8">
                    <div class="col-md-6 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Varsayilan Dusuk Stok Esigi (%)</label>
                        <input type="number" class="form-control form-control-solid" name="default_yellow_threshold" value="20" />
                    </div>
                    <div class="col-md-6 fv-row">
                        <label class="fs-6 fw-semibold mb-2">Varsayilan Kritik Stok Esigi (%)</label>
                        <input type="number" class="form-control form-control-solid" name="default_red_threshold" value="10" />
                    </div>
                </div>
                <div class="row g-9 mb-8">
                    <div class="col-md-6 fv-row">
                        <label class="fs-6 fw-semibold mb-2">SKT Yaklasan Uyari Gunu (Sari)</label>
                        <input type="number" class="form-control form-control-solid" name="expiry_yellow_days" value="30" />
                    </div>
                    <div class="col-md-6 fv-row">
                        <label class="fs-6 fw-semibold mb-2">SKT Kritik Uyari Gunu (Kirmizi)</label>
                        <input type="number" class="form-control form-control-solid" name="expiry_red_days" value="15" />
                    </div>
                </div>
                <div class="fv-row mb-8">
                    <label class="d-flex align-items-center fs-6 fw-semibold mb-2">
                        <span>E-posta Bildirimleri</span>
                    </label>
                    <div class="d-flex align-items-center mt-3">
                        <label class="form-check form-check-custom form-check-solid me-10">
                            <input class="form-check-input" type="checkbox" name="email_notifications" value="1" checked />
                            <span class="form-check-label fw-semibold text-gray-700">Aktif</span>
                        </label>
                    </div>
                </div>
                <div class="text-end">
                    <button type="submit" class="btn btn-primary">Ayarlari Kaydet</button>
                </div>
            </form>
        </div>
    </div>
@endsection
