<x-mail::message>

# Verify Your Email

Hello **{{ $user->full_name }}**,

Use this verification code to finish setting up your **{{ config('app.name') }}** account:

<div style="font-size: 30px; font-weight: 700; letter-spacing: 8px; text-align: center; margin: 24px 0;">{{ $otp }}</div>

The code expires in **5 minutes** and can be used only once. If you did not request it, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}

</x-mail::message>
