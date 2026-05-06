<div class="col-xl-6">
    <div class="card card-flush h-xl-100">
        <div class="card-header"><h3 class="card-title">Guvenlik ve Yetki</h3></div>
        <div class="card-body">
            <div class="mb-5"><span class="text-muted d-block fs-7">Sirket</span><span class="fw-bold fs-5">{{ $user->company?->name ?: '-' }}</span></div>
            <div class="mb-5"><span class="text-muted d-block fs-7">Roller</span><span class="fw-bold fs-5">{{ $user->roles->pluck('name')->implode(', ') ?: '-' }}</span></div>
            <div class="mb-5"><span class="text-muted d-block fs-7">2FA</span><span class="fw-bold fs-5">{{ $user->hasTwoFactorEnabled() ? 'Aktif' : 'Pasif' }}</span></div>
            <div class="mb-5"><span class="text-muted d-block fs-7">Durum</span><span class="fw-bold fs-5">{{ $user->is_active ? 'Aktif' : 'Pasif' }}</span></div>
            <hr class="my-8" />
            <form method="POST" action="{{ route('profile.update.password') }}">
                @csrf
                @method('PUT')
                <div class="mb-5">
                    <label class="form-label">Mevcut Sifre</label>
                    <input type="password" class="form-control form-control-solid" name="current_password" />
                </div>
                <div class="mb-5">
                    <label class="form-label">Yeni Sifre</label>
                    <input type="password" class="form-control form-control-solid" name="password" />
                </div>
                <div class="mb-5">
                    <label class="form-label">Yeni Sifre Tekrar</label>
                    <input type="password" class="form-control form-control-solid" name="password_confirmation" />
                </div>
                <button type="submit" class="btn btn-light-primary">Sifreyi Guncelle</button>
            </form>
        </div>
    </div>
</div>
