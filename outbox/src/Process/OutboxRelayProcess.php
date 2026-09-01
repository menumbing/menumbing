<?php

declare(strict_types=1);

namespace Menumbing\Outbox\Process;

use Hyperf\Coordinator\Constants;
use Hyperf\Coordinator\CoordinatorManager;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\ProcessManager;
use Menumbing\Contract\EventStream\StreamInterface;
use Menumbing\Contract\Outbox\OutboxRecord;
use Menumbing\Contract\Outbox\OutboxStorageInterface;
use Menumbing\Outbox\Event\OutboxDeadLettered;
use Menumbing\Outbox\Event\OutboxRelayed;
use Menumbing\Outbox\Event\OutboxRelayFailed;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Background worker process that relays outbox messages to the
 * actual event stream driver (Redis/Kafka).
 *
 * Each polling cycle runs within a storage transaction:
 *   1. SELECT ... FOR UPDATE SKIP LOCKED — lock pending rows
 *   2. Publish each message to the stream
 *   3. Mark as SENT or FAILED (within the same transaction)
 *   4. COMMIT — release locks
 *
 * The worker sleeps for the configured sleep_interval between cycles
 * to avoid constantly querying the database when there are no messages.
 *
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class OutboxRelayProcess extends AbstractProcess
{
    public string $name = 'outbox-relay';

    protected int $restartInterval = 1;

    protected OutboxStorageInterface $storage;
    protected StreamInterface $stream;
    protected ConfigInterface $config;
    protected EventDispatcherInterface $dispatcher;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->storage = $container->get(OutboxStorageInterface::class);
        $this->stream = $container->get(StreamInterface::class);
        $this->config = $container->get(ConfigInterface::class);
        $this->dispatcher = $container->get(EventDispatcherInterface::class);
    }

    public function handle(): void
    {
        $batchSize = $this->config->get('outbox.worker.batch_size', 100);
        $interval = $this->config->get('outbox.worker.interval', 2);
        $retryAfter = $this->config->get('outbox.worker.retry_after', 60);
        $maxRetries = $this->config->get('outbox.worker.max_retries', 5);

        while (ProcessManager::isRunning()) {
            $this->storage->transaction(function () use ($batchSize, $retryAfter, $maxRetries) {
                foreach ($this->storage->findPending($batchSize, $retryAfter, forUpdate: true) as $record) {
                    $this->relay($record, $maxRetries);
                }
            });

            if (CoordinatorManager::until(Constants::WORKER_EXIT)->yield($interval)) {
                break;
            }
        }
    }

    protected function relay(OutboxRecord $record, int $maxRetries): void
    {
        try {
            $this->stream->publish($record->message);
            $this->storage->markAsSent($record->id);

            $this->dispatcher->dispatch(new OutboxRelayed($record));
        } catch (\Throwable $e) {
            $this->storage->markAsFailed($record->id, $e);

            $willBeDeadLettered = $record->retryCount + 1 >= $maxRetries;

            if ($willBeDeadLettered) {
                $this->dispatcher->dispatch(new OutboxDeadLettered($record, $e));
            } else {
                $this->dispatcher->dispatch(new OutboxRelayFailed($record, $e));
            }
        }
    }
}
