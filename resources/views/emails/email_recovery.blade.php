@component('mail::message')
    
Hello {{$name}},

You are receiving this email because we received a password reset request for your account.

@if ($orders === 1)
#Code: {{$topic}}
@else
@component('mail::button', ['url' => $topic])
Reset Password
@endcomponent

This password reset link will expire in 60 minutes.

@endif

If you did not request a password reset, no further action is required.

@lang('Regards'),<br>{{ config('app.name') }}

@slot('subcopy')
{{-- 
@lang(
    "If you’re having trouble clicking the \"Reset Password\" button, copy and paste the URL below\n".
    'into your web browser:'
    
) {{ $topic }}
--}}
@endslot

@endcomponent