<x-mail::message>
# Confirm your login

Hello {{ $user->first_name }},

Use this six-digit code to finish signing in to AP Malls:

<x-mail::panel>
{{ $otp }}
</x-mail::panel>

This code expires in five minutes and can be used only once. If you did not try to sign in, you can safely ignore this email and consider changing your password.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
