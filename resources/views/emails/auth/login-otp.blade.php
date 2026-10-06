<x-mail::message>
# Confirm your login

Hello {{ $user->first_name }},

Use this six-digit code to finish signing in to AP Malls:

<x-mail::panel>
{{ $otp }}
</x-mail::panel>

This code expires in five minutes and can be used only once. Never share it. If you did not request this code, ignore this email; requesting a code does not grant access to your account.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
