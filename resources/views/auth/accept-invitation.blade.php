@extends('layouts.app-auth')

@section('title', 'Davet Kabul - Denti')

@section('content')
<div class="d-flex flex-column flex-root">
    <div class="d-flex flex-column flex-lg-row flex-column-fluid">
        <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1">
            <div class="d-flex flex-center flex-column flex-lg-row-fluid">
                <div class="w-lg-500px p-10 app-auth-panel">
                    <form class="form w-100" method="POST" action="{{ route('invitation.accept.store') }}">
                        @csrf
                        <input type="hidden" name="token" value="{{ $invitation->token }}" />
                        <div class="text-center mb-11">
                            <h1 class="text-gray-900 fw-bolder mb-3">Davet Kabul</h1>
                            <div class="text-gray-500 fw-semibold fs-6">
                                {{ $invitation->email }} icin gonderilen daveti tamamlayin.
                            </div>
                        </div>

                        <div class="fv-row mb-8">
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                placeholder="Ad Soyad"
                                class="form-control bg-transparent @error('name') is-invalid @enderror"
                            />
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="fv-row mb-8">
                            <input type="email" value="{{ $invitation->email }}" class="form-control form-control-solid" disabled />
                        </div>

                        <div class="fv-row mb-8">
                            <input
                                type="password"
                                name="password"
                                placeholder="Sifre"
                                class="form-control bg-transparent @error('password') is-invalid @enderror"
                            />
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="fv-row mb-8">
                            <input
                                type="password"
                                name="password_confirmation"
                                placeholder="Sifre Tekrar"
                                class="form-control bg-transparent"
                            />
                        </div>

                        <div class="d-grid mb-10">
                            <button type="submit" class="btn btn-primary">
                                <span class="indicator-label">Hesabi Olustur</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="d-flex flex-lg-row-fluid w-lg-50 bgi-size-cover bgi-position-center order-1 order-lg-2 app-auth-hero"
             style="background-image: url({{ asset('ui-kit/media/misc/auth-bg.png') }})">
            <div class="d-flex flex-column flex-center py-7 py-lg-15 px-5 px-md-15 w-100">
                <span class="badge badge-light-primary fs-2 fw-bold px-6 py-4 mb-8">{{ $invitation->role }}</span>
                <h1 class="d-none d-lg-block text-white fs-2qx fw-bolder text-center mb-7">Ekibe katil</h1>
                <div class="d-none d-lg-block text-white fs-base text-center">
                    Daveti tamamlayip klinik operasyon paneline erisim saglayin.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
