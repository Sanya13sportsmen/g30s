@component('mail::message')
# Reset password

Your code.

@component('mail::button', ['url' => ''])
{{ $code }}
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
