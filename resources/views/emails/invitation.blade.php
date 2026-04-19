<x-mail::message>
# Merhaba,

{{ $invitation->company->name }} ekibine katılmaya davet edildiniz.

Aşağıdaki butona tıklayarak şifrenizi belirleyebilir ve sisteme giriş yapabilirsiniz.

<x-mail::button :url="$inviteUrl">
Daveti Kabul Et
</x-mail::button>

Bu davet bağlantısı 24 saat içinde geçersiz olacaktır.

Teşekkürler,<br>
{{ config('app.name') }}
</x-mail::message>
