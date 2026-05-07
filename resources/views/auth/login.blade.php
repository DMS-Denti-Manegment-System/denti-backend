@extends('layouts.app-auth')

@section('title', 'Giris - Denti')

@section('content')
    <div class="d-flex flex-column flex-root">
        <div class="d-flex flex-column flex-lg-row flex-column-fluid">
            <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1">
                <div class="d-flex flex-center flex-column flex-lg-row-fluid">
                    <div class="w-lg-500px p-10 app-auth-panel">
                        <form class="form w-100" method="POST" action="{{ url('/login') }}">
                            @csrf
                            <div class="text-center mb-11">
                                <h1 class="text-gray-900 fw-bolder mb-3">Denti Yonetim</h1>
                                <div class="text-gray-500 fw-semibold fs-6">Klinik kodu, kullanici adi ve sifre ile giris
                                    yapin.</div>
                            </div>

                            <div class="fv-row mb-8">
                                <input type="text" name="clinic_code" value="{{ old('clinic_code') }}"
                                    placeholder="Klinik kodu"
                                    class="form-control bg-transparent @error('clinic_code') is-invalid @enderror" />
                                @error('clinic_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="fv-row mb-8">
                                <input type="text" name="username" value="{{ old('username') }}"
                                    placeholder="Kullanici adi veya e-posta"
                                    class="form-control bg-transparent @error('username') is-invalid @enderror" />
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="fv-row mb-8">
                                <input type="password" name="password" placeholder="Sifre"
                                    class="form-control bg-transparent @error('password') is-invalid @enderror" />
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                                <label class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="checkbox" name="remember" value="1"
                                        {{ old('remember') ? 'checked' : '' }} />
                                    <span class="form-check-label text-gray-700">Beni hatirla</span>
                                </label>
                                <a href="{{ route('admin.login') }}" class="link-primary">Super Admin girisi</a>
                            </div>

                            <div class="d-grid mb-10">
                                <button type="submit" class="btn btn-primary">
                                    <span class="indicator-label">Giris Yap</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="d-flex flex-lg-row-fluid w-lg-50 bgi-size-cover bgi-position-center order-1 order-lg-2 app-auth-hero"
                style="background-image: url({{ asset('ui-kit/media/misc/auth-bg.png') }})">
                <div class="d-flex flex-column flex-center py-7 py-lg-15 px-5 px-md-15 w-100">

                    <img class="d-none d-lg-block mx-auto w-275px w-md-50 w-xl-500px mb-10 mb-lg-20"
                        src="{{ asset('ui-kit/media/misc/auth-screens.png') }}" alt="" />
                    <h1 class="d-none d-lg-block text-white fs-2qx fw-bolder text-center mb-7">Daha hizli, daha net, daha
                        yonetilebilir stoklar.</h1>
                    <div class="d-none d-lg-block text-white fs-base text-center">
                        Stok, klinik, tedarikci tek omurgada.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
