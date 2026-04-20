<?php

declare(strict_types=1);

namespace WebScheduler\Service\Execution;

final readonly class CommandArgumentTokenizer
{
    /**
     * @return list<string>
     */
    public function tokenize(?string $arguments): array
    {
        if (null === $arguments || '' === trim($arguments)) {
            return [];
        }

        $tokens = str_getcsv(trim($arguments), ' ', '"', '\\');

        return array_values(array_filter(
            $tokens,
            static fn (?string $token): bool => null !== $token && '' !== $token,
        ));
    }
}
