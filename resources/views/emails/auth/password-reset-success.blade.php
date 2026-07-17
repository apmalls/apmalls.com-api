<x-mail::message>

# Password Reset Successful

Hello **{{ $user->full_name }}**,

This email confirms that the password for your **{{ config('app.name') }}** account has been changed successfully.

If **you made this change**, no further action is required.

If **you did not change your password**, please contact our support team immediately and secure your account.

<x-mail::button :url="config('app.frontend_url') . '/login'">
Login Now
</x-mail::button>

Thank you,<br>
{{ config('app.name') }}

</x-mail::message>
