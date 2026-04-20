<?php

declare(strict_types=1);

namespace WebScheduler\Service\Command;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class CommandRegistry
{
    public function __construct(
        #[Autowire(service: 'service_container')]
        private ContainerInterface $container,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * @return list<CommandDescriptor>
     */
    public function all(): array
    {
        if (!$this->container->hasParameter('command.definition')) {
            return [];
        }

        $ids = $this->container->getParameter('command.definition');
        $descriptors = [];
        $seen = [];

        foreach ($ids as $id) {
            try {
                if (!$this->container->has($id)) {
                    continue;
                }

                $command = $this->container->get($id);
            } catch (\Throwable $e) {
                $this->logger->warning('WebScheduler: skipping non-instantiable command', [
                    'id' => $id,
                    'reason' => $e->getMessage(),
                ]);

                continue;
            }

            if (!$command instanceof Command) {
                continue;
            }

            $name = $command->getName();

            if (null === $name || '' === $name || isset($seen[$name])) {
                continue;
            }

            $seen[$name] = true;
            $descriptors[] = new CommandDescriptor(
                name: $name,
                description: $command->getDescription(),
                hidden: $command->isHidden(),
            );
        }

        usort(
            $descriptors,
            static fn (CommandDescriptor $a, CommandDescriptor $b): int => $a->name <=> $b->name,
        );

        return $descriptors;
    }

    public function exists(string $name): bool
    {
        foreach ($this->all() as $descriptor) {
            if ($descriptor->name === $name) {
                return true;
            }
        }

        return false;
    }
}
