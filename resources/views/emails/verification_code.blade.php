@component('mail::message')
# {{ $details['title'] }}

{{ $details['body'] }}

# {{ $details['verification_code']}}

@component('mail::button', ['url' => $details['url']])
Sign in
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
