<?php

declare(strict_types=1);

namespace WebScheduler\Service\Security;

use WebScheduler\WebScheduler;

final readonly class HmacSigner
{
    public function sign(string $slug, string $secret, int $timestamp): string
    {
        return hash_hmac('sha256', $slug.'|'.$timestamp, $secret);
    }

    public function verify(string $slug, string $secret, int $timestamp, string $signature, ?int $now = null): bool
    {
        $now ??= time();

        if (abs($now - $timestamp) > WebScheduler::HMAC_TIME_WINDOW_SECONDS) {
            return false;
        }

        return hash_equals($this->sign($slug, $secret, $timestamp), $signature);
    }
}
