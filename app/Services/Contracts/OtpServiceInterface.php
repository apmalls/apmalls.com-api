<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface OtpServiceInterface
{
    public function send(
        string $recipient,
        string $channel,
        string $type
    ): array;

    public function verify(
        string $recipient,
        string $channel,
        string $type,
        string $otp
    ): bool;
}
