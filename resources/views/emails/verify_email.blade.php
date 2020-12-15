@component('mail::message')
    
#Hello!

You are receiving this email in order to complete your account registration.


@component('mail::button', ['url' => $topic])
    Verify Email
@endcomponent

This verify email link will expire in 60 minutes.


@lang('Regards'),<br>Ajo.com

@slot('subcopy')

@endslot

@endcomponent