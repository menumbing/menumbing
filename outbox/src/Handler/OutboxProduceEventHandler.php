<?php

declare(strict_types=1);

namespace Menumbing\Outbox\Handler;

use Hyperf\Di\Annotation\Inject;
use Menumbing\Contract\EventStream\StreamMessage;
use Menumbing\Contract\Outbox\OutboxStorageInterface;
use Menumbing\EventStream\Event\AfterProduce;
use Menumbing\EventStream\Event\BeforeProduce;
use Menumbing\EventStream\Event\ProduceFailed;
use Menumbing\EventStream\Handler\ProduceEventHandler;

/**
 * Replaces {@see ProduceEventHandler} to route event publishing through
 * the outbox storage instead of publishing directly to the stream.
 *
 * When configured via `event_stream.produce_handler`, this handler is
 * instantiated by {@see \Menumbing\EventStream\Listener\RegisterProducers}
 * and receives the {@see OutboxStorageInterface} via DI injection.
 *
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class OutboxProduceEventHandler extends ProduceEventHandler
{
    #[Inject]
    protected OutboxStorageInterface $outboxStorage;

    protected function produce(StreamMessage $message): void
    {
        $startTime = microtime(true);

        try {
            $this->dispatcher->dispatch(new BeforeProduce(
                $message,
                $this->driver,
                $this->annotation->driver,
                $startTime
            ));

            // Store to the outbox table instead of publishing directly to the stream.
            // This INSERT runs within the current database transaction, ensuring
            // atomicity with the business data.
            $this->outboxStorage->store($message);

            $this->dispatcher->dispatch(new AfterProduce(
                $message,
                $this->driver,
                $this->annotation->driver,
                $startTime,
                microtime(true)
            ));
        } catch (\Throwable $e) {
            $this->dispatcher->dispatch(new ProduceFailed(
                $e,
                $message,
                $this->driver,
                $this->annotation->driver,
                $startTime,
                microtime(true)
            ));

            throw $e;
        }
    }
}
