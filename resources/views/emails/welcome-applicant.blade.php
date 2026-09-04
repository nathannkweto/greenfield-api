@component('mail::message')
# Welcome, {{ $applicant->first_name }}!

Your applicant account has been successfully created. Below are your temporary login credentials:

**Email:** {{ $applicant->email }}
**Password:** {{ $password }}

@component('mail::button', ['url' => config('app.url') . '/login'])
Log In to Your Account
@endcomponent

Please log in and change your password as soon as possible.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
