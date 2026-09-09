@component('mail::message')
# Welcome, {{ $applicant->first_name }}!

Your account on the Greenfield College platform has been successfully created. Below are your temporary login credentials:

**Email:** {{ $applicant->email }}<br>
**Password:** {{ $password }}

@component('mail::button', ['url' => config('app.frontend-url') . '/login'])
Log In to Your Account
@endcomponent

Please log in and continue your application.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
