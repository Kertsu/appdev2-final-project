@component('mail::message')
# {{ $details['title'] }}

{{ $details['body'] }}

@component('mail::button', ['url' => $details['url']])
Click Here
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
