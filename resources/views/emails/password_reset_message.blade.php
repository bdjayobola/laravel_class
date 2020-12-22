@component('mail::message')
    
Hello {{$name}},

Your password has been successfully reset.

@lang('Regards'),<br>Ajo.com

@slot('subcopy')

@endslot

@endcomponent