<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\OTP\OtpVerification;

interface OtpVerificationRepositoryInterface
{
    public function create(array $data): OtpVerification;

    public function findActive(
        string $recipient,
        string $channel,
        string $type
    ): ?OtpVerification;

    public function deleteOld(
        string $recipient,
        string $channel,
        string $type
    ): void;

    public function update(
        int $id,
        array $data
    ): OtpVerification;
}
