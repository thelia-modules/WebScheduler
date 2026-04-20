<?php

declare(strict_types=1);

namespace WebScheduler\Service\Capability;

use WebScheduler\Enum\CapabilityEnum;
use WebScheduler\Model\WebSchedulerCapability;
use WebScheduler\Model\WebSchedulerCapabilityQuery;

final readonly class CapabilityRepository
{
    public function __construct(
        private CapabilityProber $prober,
        private int $cacheTtlSeconds = 3600,
    ) {
    }

    public function get(): CapabilityReport
    {
        $rows = WebSchedulerCapabilityQuery::create()->find();

        if (0 === $rows->count() || $this->isStale($rows->toArray())) {
            return $this->refresh();
        }

        $availability = [];
        $details = [];
        $checkedAt = new \DateTimeImmutable();

        foreach ($rows as $row) {
            $availability[$row->getCapabilityKey()] = $row->getAvailable();
            $details[$row->getCapabilityKey()] = $this->decodeDetails($row->getDetails());
            $rowDate = $row->getCheckedAt(\DateTimeImmutable::class);
            if ($rowDate instanceof \DateTimeImmutable && $rowDate < $checkedAt) {
                $checkedAt = $rowDate;
            }
        }

        return new CapabilityReport($availability, $details, $checkedAt);
    }

    public function refresh(): CapabilityReport
    {
        $report = $this->prober->probe();

        WebSchedulerCapabilityQuery::create()->deleteAll();

        foreach (CapabilityEnum::cases() as $capability) {
            $row = (new WebSchedulerCapability())
                ->setCapabilityKey($capability->value)
                ->setAvailable($report->isAvailable($capability))
                ->setDetails(json_encode($report->details($capability), \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR))
                ->setCheckedAt($report->checkedAt);

            $row->save();
        }

        return $report;
    }

    /**
     * @param list<array<string, mixed>> $rows
     */
    private function isStale(array $rows): bool
    {
        $oldest = null;

        foreach ($rows as $row) {
            $checkedAt = $row['CheckedAt'] ?? null;

            if (!$checkedAt instanceof \DateTimeInterface) {
                return true;
            }

            if (null === $oldest || $checkedAt < $oldest) {
                $oldest = $checkedAt;
            }
        }

        return null === $oldest
            || (new \DateTimeImmutable())->getTimestamp() - $oldest->getTimestamp() > $this->cacheTtlSeconds;
    }

    private function decodeDetails(?string $payload): array
    {
        if (null === $payload || '' === $payload) {
            return [];
        }

        try {
            $decoded = json_decode($payload, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return \is_array($decoded) ? $decoded : [];
    }
}
