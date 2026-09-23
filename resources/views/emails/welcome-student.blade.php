@component('mail::message')
# Welcome, {{ $student->first_name }}!

Your student registration on the {{ config('app.name') }} platform is complete. Below are your account credentials and details:

**Student Number:** {{ $student->student_number }}<br>
**Email:** {{ $student->email }}
@if($password)
<br>**Password:** {{ $password }}
@endif

@component('mail::button', ['url' => config('app.frontend-url', config('app.url')) . '/login'])
Log In to Student Portal
@endcomponent

Use your email and password to login to the student portal.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
