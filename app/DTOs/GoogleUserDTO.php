<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class GoogleUserDTO
{
    public function __construct(

        public string $googleId,

        public string $firstName,

        public ?string $lastName,

        public string $email,

        public ?string $avatar,

    ) {
    }
}
