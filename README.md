<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## AP Malls Email Authentication

Customer registration and staff invitations require working email delivery. Configure `FRONTEND_URL` and the `MAIL_*` values in `.env` before testing these flows.

Use SMTP and the database queue in the backend `.env` (never in the frontend):

```dotenv
MAIL_MAILER=smtp
QUEUE_CONNECTION=database
```

Set the SMTP host, port, scheme, username, password and sender for your provider. Never use a log mailer or commit SMTP credentials. Mailables are queued after their database transaction commits. A successful request means queued, not delivered to an inbox. Keep a supervised database queue worker running:

```bash
php artisan queue:work database --queue=default --sleep=1 --tries=3
```

After environment changes, run `php artisan config:clear` (or rebuild production config with `config:cache`) and `php artisan queue:restart`. Monitor worker availability and `php artisan queue:failed`; investigate SMTP or queue failures before retrying jobs with `php artisan queue:retry <id>`. Do not dump queue payloads into logs: queued mail contains sensitive verification material. A stopped worker leaves emails in `jobs`, and codes expire five minutes after generation, not delivery. Old queued messages may contain replaced or expired codes; only the newest valid code works.

All active, verified roles can choose either sign-in method:

- `POST /api/v1/auth/login` accepts email/password and immediately returns the normal authenticated response. It sends no OTP and does not depend on mail delivery. Verified demo accounts without real mailboxes can use this method.
- Password requests are limited to ten per minute per visitor IP to limit password guessing.
- `POST /api/v1/auth/login/send-otp` accepts only email and returns HTTP 202 with `code: login_otp_required`, an opaque challenge ID, masked destination, expiry and resend timing. It never returns a token or user. Complete login through `/auth/login/verify-otp`; resend through `/auth/login/resend-otp`, both using that challenge ID.
- Login codes are hashed, single-use, valid for five minutes and limited to five attempts. Initial requests and resends share a 60-second per-account cooldown and independent five-send/hour account and IP limits. Replaced codes and outstanding challenges after a successful login cannot be used.
- Unknown, inactive, unverified and pending-activation accounts receive the same OTP-request rejection. Both methods require verification; neither creates an account or bypasses staff activation. Generic OTP routes still reject the `login` purpose.

Signup continues requiring email OTP verification before any session is issued. Staff must use their 24-hour activation link to set a password. Google authentication, password resets and re-verification after email changes retain their existing behavior. Active sessions and cookie duration remain unchanged. This is a choice of password or passwordless email OTP, not mandatory two-factor authentication. SMTP/queue failure blocks OTP login and signup verification; password login is an explicit alternative for already verified accounts.

For per-visitor IP throttling behind Next.js, configure backend `TRUSTED_PROXIES` with only the frontend/reverse-proxy IP addresses or CIDRs (default: loopback). Login OTP proxies forward `X-Forwarded-For`; the hosting edge must overwrite untrusted incoming forwarding headers. Never trust all public API callers. Without a trusted forwarding setup, requests share the proxy's five-send/hour IP quota. Accounts sharing one public IP also share that quota.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

In addition, [Laracasts](https://laracasts.com) contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

You can also watch bite-sized lessons with real-world projects on [Laravel Learn](https://laravel.com/learn), where you will be guided through building a Laravel application from scratch while learning PHP fundamentals.

## Agentic Development

Laravel's predictable structure and conventions make it ideal for AI coding agents like Claude Code, Cursor, and GitHub Copilot. Install [Laravel Boost](https://laravel.com/docs/ai) to supercharge your AI workflow:

```bash
composer require laravel/boost --dev

php artisan boost:install
```

Boost provides your agent 15+ tools and skills that help agents build Laravel applications while following best practices.

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
