@extends('layouts.app')

@section('title', 'Raporlar - Denti')
@section('page-title', 'Operasyonel Raporlar')
@section('page-subtitle', 'Sistem geneli özet ve hareket dökümü')

@section('content')
    <div class="app-module-shell">
        <div class="mb-2">
            @include('operations.reports.components.summary-cards')
        </div>

        <div class="card card-flush app-toolbar-card app-module-toolbar">
            <div class="card-body">
                <form method="GET" action="{{ route('reports.index') }}" class="row g-5 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label">Klinik Filtresi</label>
                        <select name="clinic_id" class="form-select form-select-solid" data-control="select2" data-placeholder="Tum Klinikler">
                            <option value=""></option>
                            @foreach($clinics as $clinic)
                                <option value="{{ $clinic->id }}" @selected(request('clinic_id') == $clinic->id)>{{ $clinic->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Baslangic Tarihi</label>
                        <input type="date" name="date_from" class="form-control form-control-solid" value="{{ request('date_from') }}" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Bitis Tarihi</label>
                        <input type="date" name="date_to" class="form-control form-control-solid" value="{{ request('date_to') }}" />
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-grow-1">Filtrele</button>
                        <a href="{{ route('reports.index') }}" class="btn btn-light">Temizle</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-flush app-table-card app-module-table">
        <div class="card-header align-items-center py-5 gap-2 gap-md-5">
            <div class="card-title">
                <h2>Son Stok Hareketleri</h2>
            </div>
            <div class="card-toolbar">
                <button type="button" class="btn btn-light-primary btn-sm" onclick="window.print()">
                    <i class="ki-duotone ki-printer fs-3 me-1"><span class="path1"></span><span class="path2"></span></i> Yazdır
                </button>
            </div>
        </div>
        <div class="card-body pt-0">
            <div class="table-responsive">
                <table class="table align-middle table-row-dashed fs-6 gy-5">
                    <thead>
                        <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                            <th>Tarih</th>
                            <th>Ürün</th>
                            <th>Klinik</th>
                            <th>İşlem Tipi</th>
                            <th>Miktar</th>
                            <th>Yeni Stok</th>
                            <th>Kullanıcı</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600 fw-semibold">
                        @forelse($movements as $move)
                            <tr>
                                <td>{{ $move->transaction_date->format('d/m/Y H:i') }}</td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="text-gray-900 fw-bold">{{ $move->stock->product->name ?? 'Silinmiş Ürün' }}</span>
                                        <span class="text-muted fs-7">#{{ $move->stock_id }}</span>
                                    </div>
                                </td>
                                <td>{{ $move->clinic->name ?? 'Genel' }}</td>
                                <td>
                                    <span class="badge badge-light-{{ in_array($move->type, ['purchase', 'adjustment_increase', 'transfer_in']) ? 'success' : 'danger' }}">
                                        {{ $move->type_text }}
                                    </span>
                                </td>
                                <td class="fw-bold text-{{ in_array($move->type, ['purchase', 'adjustment_increase', 'transfer_in']) ? 'success' : 'danger' }}">
                                    {{ in_array($move->type, ['purchase', 'adjustment_increase', 'transfer_in']) ? '+' : '-' }}{{ $move->quantity }}
                                </td>
                                <td class="fw-bold">{{ $move->new_stock }}</td>
                                <td>{{ $move->performed_by }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-10">Kayıt bulunamadı.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="d-flex flex-stack flex-wrap pt-10">
                <x-pager :paginator="$movements" />
            </div>
        </div>
    </div>
    </div>
@endsection
