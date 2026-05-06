<div class="col-xl-6">
    <div class="card card-flush h-xl-100">
        <div class="card-header"><h3 class="card-title">Kullanici Bilgileri</h3></div>
        <div class="card-body">
            <form method="POST" action="{{ route('profile.update.info') }}">
                @csrf
                @method('PUT')
                <div class="mb-5">
                    <label class="form-label">Ad Soyad</label>
                    <input class="form-control form-control-solid" name="name" value="{{ old('name', $user->name) }}" />
                </div>
                <div class="mb-5">
                    <label class="form-label">Kullanici Adi</label>
                    <input class="form-control form-control-solid" value="{{ $user->username ?: '-' }}" disabled />
                </div>
                <div class="mb-5">
                    <label class="form-label">E-posta</label>
                    <input class="form-control form-control-solid" name="email" value="{{ old('email', $user->email) }}" />
                </div>
                <div class="mb-5">
                    <label class="form-label">Klinik</label>
                    <input class="form-control form-control-solid" value="{{ $user->clinic?->name ?: '-' }}" disabled />
                </div>
                <button type="submit" class="btn btn-primary">Bilgileri Guncelle</button>
            </form>
        </div>
    </div>
</div>
