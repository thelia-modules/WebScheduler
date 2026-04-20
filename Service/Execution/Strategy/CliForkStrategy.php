<?php

declare(strict_types=1);

namespace WebScheduler\Service\Execution\Strategy;

use Symfony\Component\Process\Exception\ProcessStartFailedException;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use WebScheduler\Enum\CapabilityEnum;
use WebScheduler\Enum\ExecutionStrategyEnum;
use WebScheduler\Model\WebSchedulerTask;
use WebScheduler\Service\Capability\CapabilityReport;
use WebScheduler\Service\Execution\CommandArgumentTokenizer;
use WebScheduler\Service\Execution\ExecutionContext;
use WebScheduler\Service\Execution\ExecutionResult;
use WebScheduler\Service\Execution\ExecutionStrategyInterface;

final readonly class CliForkStrategy implements ExecutionStrategyInterface
{
    public function __construct(
        private CommandArgumentTokenizer $tokenizer,
    ) {
    }

    public function name(): ExecutionStrategyEnum
    {
        return ExecutionStrategyEnum::CLI_FORK;
    }

    public function priority(): int
    {
        return 100;
    }

    public function supports(CapabilityReport $report): bool
    {
        return $report->isAvailable(CapabilityEnum::PROC_OPEN)
            && $report->isAvailable(CapabilityEnum::PHP_CLI_BINARY)
            && !$this->isWindows();
    }

    public function execute(ExecutionContext $context): ExecutionResult
    {
        $outputFile = $this->outputFilePath($context->executionId);
        $this->ensureOutputDirectory();

        $binary = $this->phpBinary();
        $theliaBin = \THELIA_ROOT.'Thelia';

        $taskCmd = $this->buildTokenCommand($context->task, $binary, $theliaBin);
        $finalizeCmd = sprintf(
            '%s %s web-scheduler:finalize-execution --id=%d --rc=$rc --output-file=%s',
            escapeshellarg($binary),
            escapeshellarg($theliaBin),
            $context->executionId,
            escapeshellarg($outputFile),
        );

        $shellScript = sprintf(
            '%s > %s 2>&1; rc=$?; %s',
            $taskCmd,
            escapeshellarg($outputFile),
            $finalizeCmd,
        );

        $process = Process::fromShellCommandline(
            sprintf('nohup sh -c %s > /dev/null 2>&1 &', escapeshellarg($shellScript)),
            \THELIA_ROOT,
        );
        $process->setTimeout(null);
        $process->disableOutput();

        try {
            $process->run();
        } catch (ProcessStartFailedException $e) {
            return ExecutionResult::failed($this->name(), $e->getMessage());
        }

        return ExecutionResult::detached(
            $this->name(),
            sprintf('Detached shell command dispatched at %s.', (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM)),
        );
    }

    private function buildTokenCommand(WebSchedulerTask $task, string $binary, string $theliaBin): string
    {
        $parts = [
            $binary,
            $theliaBin,
            $task->getCommandName(),
            ...$this->tokenizer->tokenize($task->getCommandArguments()),
        ];

        return implode(' ', array_map(static fn (string $part): string => escapeshellarg($part), $parts));
    }

    private function phpBinary(): string
    {
        $binary = (new PhpExecutableFinder())->find(false);

        if (false === $binary) {
            throw new \RuntimeException('Unable to locate the PHP binary.');
        }

        return $binary;
    }

    private function outputFilePath(int $executionId): string
    {
        return $this->outputDirectory().$executionId.'.log';
    }

    private function outputDirectory(): string
    {
        return \THELIA_CACHE_DIR.'webscheduler_output'.\DIRECTORY_SEPARATOR;
    }

    private function ensureOutputDirectory(): void
    {
        $dir = $this->outputDirectory();

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    private function isWindows(): bool
    {
        return '\\' === \DIRECTORY_SEPARATOR;
    }
}
