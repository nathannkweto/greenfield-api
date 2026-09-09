@component('mail::message')
# Welcome, {{ $admin->first_name }}!

Your administrator account has been successfully created. Below are your account login credentials:

**Email:** {{ $admin->user->email }}<br>
**Password:** {{ $password }}

**Department:** {{ $admin->department }}<br>
**Position:** {{ $admin->position }}

@component('mail::button', ['url' => config('app.frontend-url') . '/login'])
Log In to Administrative Portal
@endcomponent

Please log in and update your password immediately for security purposes.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
