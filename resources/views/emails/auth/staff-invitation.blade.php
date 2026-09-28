<x-mail::message>

# Activate Your Account

Hello **{{ $user->full_name }}**,

An AP Malls administrator created an account for you. Use the secure link below to verify your email address and choose your password.

<x-mail::button :url="$activationUrl">
Activate Account
</x-mail::button>

This link expires in **24 hours** and can be used only once. If you were not expecting this invitation, contact your administrator.

Thanks,<br>
{{ config('app.name') }}

</x-mail::message>
