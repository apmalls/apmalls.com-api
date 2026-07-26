<?php

declare(strict_types=1);

namespace App\Repositories\OTP;


use App\Models\OTP\OtpVerification;
use App\Repositories\Contracts\OtpVerificationRepositoryInterface;

class OtpVerificationRepository implements OtpVerificationRepositoryInterface
{
    public function create(array $data): OtpVerification
    {
        return OtpVerification::create($data);
    }

    public function findActive(
        string $recipient,
        string $channel,
        string $type
    ): ?OtpVerification {

        return OtpVerification::query()

            ->where('recipient', $recipient)

            ->where('channel', $channel)

            ->where('type', $type)

            ->where('is_verified', false)

            ->latest()

            ->first();
    }

    public function deleteOld(
        string $recipient,
        string $channel,
        string $type
    ): void {

        OtpVerification::query()

            ->where('recipient', $recipient)

            ->where('channel', $channel)

            ->where('type', $type)

            ->delete();
    }

    public function update(
        int $id,
        array $data
    ): OtpVerification {

        $otp = OtpVerification::findOrFail($id);

        $otp->update($data);

        return $otp->refresh();
    }
}
