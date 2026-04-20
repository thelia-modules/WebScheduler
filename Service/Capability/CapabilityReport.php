<?php

declare(strict_types=1);

namespace WebScheduler\Service\Capability;

use WebScheduler\Enum\CapabilityEnum;

final readonly class CapabilityReport
{
    /**
     * @param array<string, bool> $availability keyed by CapabilityEnum::value
     * @param array<string, array<string, mixed>> $details keyed by CapabilityEnum::value
     */
    public function __construct(
        private array $availability,
        private array $details,
        public \DateTimeImmutable $checkedAt,
    ) {
    }

    public function isAvailable(CapabilityEnum $capability): bool
    {
        return $this->availability[$capability->value] ?? false;
    }

    public function details(CapabilityEnum $capability): array
    {
        return $this->details[$capability->value] ?? [];
    }

    public function all(): array
    {
        return $this->availability;
    }
}
