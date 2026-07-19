<x-mail::message>

# Reset Your Password

Hello **{{ $user->full_name }}**,

We received a request to reset the password for your **{{ config('app.name') }}** account.

If you requested this password reset, click the button below.

<x-mail::button :url="$resetUrl">
Reset Password
</x-mail::button>

This password reset link will expire in **60 minutes**.

If you did not request a password reset, no further action is required. Your account remains secure.

Thanks,<br>
{{ config('app.name') }}

</x-mail::message>
