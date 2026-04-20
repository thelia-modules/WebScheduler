<?php

declare(strict_types=1);

namespace WebScheduler\Service\Security;

final readonly class SecretGenerator
{
    public function generateSlug(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }
}
