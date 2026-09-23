@component('mail::message')
# Welcome, {{ $lecturer->first_name }} {{ $lecturer->last_name }}!

Your lecturer profile and account on the Greenfield College portal have been created successfully. Below are your login credentials:

**Email:** {{ $lecturer->user?->email ?? $lecturer->email }}<br>
**Password:** {{ $password }}

@component('mail::button', ['url' => config('app.frontend-url') . '/login'])
Log In to College Portal
@endcomponent

Please log in and find courses you have been assigned.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
