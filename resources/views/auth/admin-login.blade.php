@extends('layouts.metronic-auth')

@section('title', 'Super Admin Girisi - Denti')

@section('content')
<div class="d-flex flex-column flex-root">
    <div class="d-flex flex-column flex-lg-row flex-column-fluid">
        <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1">
            <div class="d-flex flex-center flex-column flex-lg-row-fluid">
                <div class="w-lg-500px p-10 app-auth-panel">
                    <form class="form w-100" method="POST" action="{{ route('admin.login.store') }}">
                        @csrf
                        <div class="text-center mb-11">
                            <h1 class="text-gray-900 fw-bolder mb-3">Super Admin Girisi</h1>
                            <div class="text-gray-500 fw-semibold fs-6">Sistem yoneticisi hesabi ile panele girin.</div>
                        </div>

                        <div class="fv-row mb-8">
                            <input
                                type="text"
                                name="username"
                                value="{{ old('username') }}"
                                placeholder="Kullanici adi"
                                class="form-control bg-transparent @error('username') is-invalid @enderror"
                            />
                            @error('username')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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

                        <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }} />
                                <span class="form-check-label text-gray-700">Beni hatirla</span>
                            </label>
                            <a href="{{ route('login') }}" class="link-primary">Klinik girisine don</a>
                        </div>

                        <div class="d-grid mb-10">
                            <button type="submit" class="btn btn-danger">
                                <span class="indicator-label">Admin Girisi</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="d-flex flex-lg-row-fluid w-lg-50 bgi-size-cover bgi-position-center order-1 order-lg-2 app-auth-hero"
             style="background-image: url({{ asset('metronic/assets/media/misc/auth-bg.png') }})">
            <div class="d-flex flex-column flex-center py-7 py-lg-15 px-5 px-md-15 w-100">
                <span class="badge badge-light-danger fs-2 fw-bold px-6 py-4 mb-8">Restricted Access</span>
                <h1 class="d-none d-lg-block text-white fs-2qx fw-bolder text-center mb-7">Platform yonetimi tek merkezden</h1>
                <div class="d-none d-lg-block text-white fs-base text-center">
                    Sirketler, sistem rolleri ve genel operasyon denetimi icin ayrilmis alan.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
