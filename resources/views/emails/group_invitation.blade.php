@component('mail::message')
    
Hello {{$name}},

Mr {{$orders}} invited you to join thrift group on our platform, click on the below button to view group.


@component('mail::button', ['url' => $topic])
    Group Invite Link
@endcomponent




@lang('Regards'),<br>Ajo.com

@slot('subcopy')

@endslot

@endcomponent