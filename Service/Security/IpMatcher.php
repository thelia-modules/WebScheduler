<?php

declare(strict_types=1);

namespace WebScheduler\Service\Security;

use Symfony\Component\HttpFoundation\IpUtils;

final readonly class IpMatcher
{
    /**
     * @param list<string> $allowlist
     */
    public function matches(?string $ip, array $allowlist): bool
    {
        if ([] === $allowlist) {
            return true;
        }

        if (null === $ip) {
            return false;
        }

        return IpUtils::checkIp($ip, $allowlist);
    }

    public function parse(?string $payload): array
    {
        if (null === $payload || '' === trim($payload)) {
            return [];
        }

        $decoded = json_decode($payload, true);

        if (\is_array($decoded)) {
            return array_values(array_filter(array_map(
                static fn ($v): string => \is_string($v) ? trim($v) : '',
                $decoded,
            )));
        }

        return array_values(array_filter(array_map(
            static fn (string $line): string => trim($line),
            preg_split('/[\s,]+/', $payload) ?: [],
        )));
    }

    /**
     * @param list<string> $entries
     */
    public function serialize(array $entries): ?string
    {
        $clean = array_values(array_filter(array_map(
            static fn (string $e): string => trim($e),
            $entries,
        )));

        return [] === $clean ? null : json_encode($clean, \JSON_THROW_ON_ERROR);
    }
}
