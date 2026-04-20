<?php

declare(strict_types=1);

namespace WebScheduler\Service\Execution;

final readonly class InProcessRunResult
{
    public function __construct(
        public int $exitCode,
        public string $output,
        public bool $failed,
    ) {
    }
}
