<?php

declare(strict_types=1);

namespace WebScheduler\Service\Execution;

use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use WebScheduler\Model\WebSchedulerTask;

final readonly class ExternalProcessFactory
{
    public function __construct(
        private CommandArgumentTokenizer $tokenizer,
    ) {
    }

    public function create(WebSchedulerTask $task, ?float $timeoutSeconds): Process
    {
        $command = [
            $this->phpBinary(),
            $this->consolePath(),
            $task->getCommandName(),
            ...$this->tokenizer->tokenize($task->getCommandArguments()),
        ];

        $process = new Process($command, \THELIA_ROOT, [
            'THELIA_ENV' => $_SERVER['THELIA_ENV'] ?? $_ENV['THELIA_ENV'] ?? 'prod',
        ]);

        $process->setTimeout($timeoutSeconds);

        return $process;
    }

    private function phpBinary(): string
    {
        $binary = (new PhpExecutableFinder())->find(false);

        if (false === $binary) {
            throw new \RuntimeException('Unable to locate the PHP binary.');
        }

        return $binary;
    }

    private function consolePath(): string
    {
        return \THELIA_ROOT.'Thelia';
    }
}
