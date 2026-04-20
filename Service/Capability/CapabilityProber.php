<?php

declare(strict_types=1);

namespace WebScheduler\Service\Capability;

use WebScheduler\Enum\CapabilityEnum;

final readonly class CapabilityProber
{
    public function probe(): CapabilityReport
    {
        $availability = [];
        $details = [];

        $disabled = $this->disabledFunctions();

        $availability[CapabilityEnum::FASTCGI_FINISH_REQUEST->value] = $this->functionUsable('fastcgi_finish_request', $disabled);
        $details[CapabilityEnum::FASTCGI_FINISH_REQUEST->value] = ['sapi' => \PHP_SAPI];

        $availability[CapabilityEnum::PROC_OPEN->value] = $this->functionUsable('proc_open', $disabled);
        $details[CapabilityEnum::PROC_OPEN->value] = ['disabled_functions' => $disabled];

        $binary = $this->resolvePhpBinary();
        $availability[CapabilityEnum::PHP_CLI_BINARY->value] = null !== $binary && is_executable($binary);
        $details[CapabilityEnum::PHP_CLI_BINARY->value] = ['path' => $binary];

        $availability[CapabilityEnum::SET_TIME_LIMIT->value] = $this->functionUsable('set_time_limit', $disabled) && !$this->isSafeModeLike();
        $details[CapabilityEnum::SET_TIME_LIMIT->value] = ['max_execution_time' => (int) \ini_get('max_execution_time')];

        $availability[CapabilityEnum::IGNORE_USER_ABORT->value] = $this->functionUsable('ignore_user_abort', $disabled);
        $details[CapabilityEnum::IGNORE_USER_ABORT->value] = [];

        return new CapabilityReport($availability, $details, new \DateTimeImmutable());
    }

    /**
     * @param list<string> $disabled
     */
    private function functionUsable(string $function, array $disabled): bool
    {
        return \function_exists($function) && !\in_array(strtolower($function), $disabled, true);
    }

    /**
     * @return list<string>
     */
    private function disabledFunctions(): array
    {
        $raw = (string) \ini_get('disable_functions');

        if ('' === $raw) {
            return [];
        }

        return array_map(
            static fn (string $entry): string => strtolower(trim($entry)),
            explode(',', $raw),
        );
    }

    private function resolvePhpBinary(): ?string
    {
        if (\defined('PHP_BINARY') && '' !== \PHP_BINARY && is_file(\PHP_BINARY)) {
            return \PHP_BINARY;
        }

        $candidates = ['/usr/bin/php', '/usr/local/bin/php'];

        foreach ($candidates as $candidate) {
            if (is_file($candidate) && is_executable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function isSafeModeLike(): bool
    {
        return '' !== (string) \ini_get('open_basedir')
            && false === \str_contains((string) \ini_get('open_basedir'), \dirname(\PHP_BINARY ?? ''));
    }
}
