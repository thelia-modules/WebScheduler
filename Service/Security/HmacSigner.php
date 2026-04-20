<?php

declare(strict_types=1);

namespace WebScheduler\Service\Security;

final readonly class HmacSigner
{
    public function sign(string $slug, string $secret): string
    {
        return hash_hmac('sha256', $slug, $secret);
    }

    public function verify(string $slug, string $secret, string $signature): bool
    {
        return hash_equals($this->sign($slug, $secret), $signature);
    }
}
