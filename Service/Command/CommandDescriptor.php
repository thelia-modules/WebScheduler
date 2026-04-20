<?php

declare(strict_types=1);

namespace WebScheduler\Service\Command;

final readonly class CommandDescriptor
{
    public function __construct(
        public string $name,
        public string $description,
        public bool $hidden,
    ) {
    }
}
