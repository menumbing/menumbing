<?php

declare(strict_types=1);

namespace Menumbing\Outbox\Command;

use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\ConfigInterface;
use Menumbing\Contract\Outbox\OutboxStorageInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Prunes sent outbox messages older than the configured retention period.
 *
 * Intended to be run via cron:
 *   0 3 * * * php bin/hyperf.php outbox:prune
 *
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
#[Command]
class PruneOutboxCommand extends HyperfCommand
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('outbox:prune');
    }

    public function handle(): void
    {
        $config = $this->container->get(ConfigInterface::class);

        $retentionDays = (int) ($this->input->getOption('days')
            ?? $config->get('outbox.prune.retention_days', 7));

        $storage = $this->container->get(OutboxStorageInterface::class);

        $deleted = $storage->prune($retentionDays);

        $this->info(sprintf('Pruned %d sent messages older than %d days.', $deleted, $retentionDays));
    }

    protected function configure(): void
    {
        $this->setDescription('Delete sent outbox messages older than the retention period.');
        $this->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Number of days to retain sent messages.', null);
    }
}
