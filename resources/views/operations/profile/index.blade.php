@extends('layouts.app')
@section('title', 'Profil - Denti')
@section('page-title', 'Profil')
@section('page-subtitle', 'Hesap ozeti')
@section('content')
    <div class="row g-5 g-xl-8">
        @include('operations.profile.components.info-form')
        @include('operations.profile.components.security-form')
    </div>
@endsection

@push('modals')
    <!-- 2FA Setup Modal -->
    <div class="modal fade" id="setup2FAModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-450px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">2FA Kurulumu</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body py-10 px-lg-17 text-center">
                    <p class="text-muted mb-7">Google Authenticator veya benzeri bir uygulama ile aşağıdaki QR kodu taratın.</p>
                    <div id="qrCodeContainer" class="mb-7 d-flex justify-content-center">
                        <!-- QR Code will be injected here -->
                        <div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>
                    </div>
                    <div class="mb-10 text-start">
                        <label class="form-label required">Doğrulama Kodu</label>
                        <input type="text" id="twoFactorCode" class="form-control form-control-solid" placeholder="6 haneli kod" maxlength="6" />
                    </div>
                    <button type="button" class="btn btn-primary w-100" id="confirm2FABtn">Doğrula ve Aktif Et</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recovery Codes Modal -->
    <div class="modal fade" id="recoveryCodesModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered mw-500px">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="fw-bold">Kurtarma Kodları</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </div>
                </div>
                <div class="modal-body py-10 px-lg-17">
                    <div class="alert alert-warning d-flex align-items-center p-5 mb-10">
                        <i class="ki-duotone ki-information-5 fs-2hx text-warning me-4"><span class="path1"></span><span class="path2"></span><span class="path3"></span></i>
                        <div class="d-flex flex-column">
                            <h4 class="mb-1 text-dark">Bu kodları güvenli bir yerde saklayın!</h4>
                            <span>Telefonunuza erişiminizi kaybederseniz bu kodlar ile giriş yapabilirsiniz.</span>
                        </div>
                    </div>
                    <div id="recoveryCodesList" class="bg-light rounded p-5 mb-7 font-monospace fs-5 text-center">
                        <!-- Codes will be injected here -->
                    </div>
                    <button type="button" class="btn btn-light-primary w-100" id="regenerateRecoveryCodesBtn">Yeni Kodlar Üret</button>
                </div>
            </div>
        </div>
    </div>
@endpush

@push('scripts')
    <script>
        $(function () {
            $('#setup2FA').on('click', function() {
                $('#setup2FAModal').modal('show');
                $('#qrCodeContainer').html('<div class="spinner-border text-primary" role="status"></div>');
                
                $.post('{{ route('profile.2fa.generate') }}', { _token: '{{ csrf_token() }}' }, function(res) {
                    $('#qrCodeContainer').html('<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' + encodeURIComponent(res.qrUrl) + '" alt="QR Code" />');
                });
            });

            $('#confirm2FABtn').on('click', function() {
                const code = $('#twoFactorCode').val();
                if (!code) return;

                $(this).prop('disabled', true).text('Doğrulanıyor...');

                $.post('{{ route('profile.2fa.confirm') }}', { _token: '{{ csrf_token() }}', code: code }, function(res) {
                    if (res.success) {
                        $('#setup2FAModal').modal('hide');
                        Swal.fire({
                            text: "2FA başarıyla aktif edildi.",
                            icon: "success",
                            buttonsStyling: false,
                            confirmButtonText: "Tamam",
                            customClass: { confirmButton: "btn btn-primary" }
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({ text: res.message, icon: "error", buttonsStyling: false, confirmButtonText: "Tamam", customClass: { confirmButton: "btn btn-danger" } });
                        $('#confirm2FABtn').prop('disabled', false).text('Doğrula ve Aktif Et');
                    }
                });
            });

            $('#showRecoveryCodes').on('click', function() {
                $('#recoveryCodesModal').modal('show');
                loadRecoveryCodes();
            });

            $('#regenerateRecoveryCodesBtn').on('click', function() {
                $.post('{{ route('profile.2fa.recovery-codes') }}', { _token: '{{ csrf_token() }}' }, function(res) {
                    displayRecoveryCodes(res.recoveryCodes);
                });
            });

            function loadRecoveryCodes() {
                // Initial load from user object if available or via API
                @if($user->two_factor_recovery_codes)
                    displayRecoveryCodes(@json($user->two_factor_recovery_codes));
                @else
                     $.post('{{ route('profile.2fa.recovery-codes') }}', { _token: '{{ csrf_token() }}' }, function(res) {
                        displayRecoveryCodes(res.recoveryCodes);
                    });
                @endif
            }

            function displayRecoveryCodes(codes) {
                let html = '<div class="row">';
                codes.forEach(code => {
                    html += '<div class="col-6 mb-2">' + code + '</div>';
                });
                html += '</div>';
                $('#recoveryCodesList').html(html);
            }
        });
    </script>
@endpush
