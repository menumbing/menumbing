<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Handler;

use Menumbing\Contract\EventStream\StreamInterface;
use Menumbing\Contract\EventStream\StreamMessage;
use Menumbing\EventStream\Annotation\ProducedEvent;
use Menumbing\EventStream\Event\AfterProduce;
use Menumbing\EventStream\Event\BeforeProduce;
use Menumbing\EventStream\Event\ProduceFailed;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ProduceEventHandler
{
    public function __construct(
        protected StreamInterface $driver,
        protected ProducedEvent $annotation,
        protected EventDispatcherInterface $dispatcher,
    ) {
    }

    public function __invoke(object $event): void
    {
        $this->produce(
            $this->buildMessage($event)
        );
    }

    protected function buildMessage(object $event): StreamMessage
    {
        return new StreamMessage(
            stream: $this->annotation->stream,
            type: $this->annotation->name,
            data: $event,
        );
    }

    protected function produce(StreamMessage $message): void
    {
        $startTime = microtime(true);

        try {
            $this->dispatcher->dispatch(new BeforeProduce($message, $this->driver, $this->annotation->driver, $startTime));
            $this->driver->publish($message);
            $this->dispatcher->dispatch(new AfterProduce($message, $this->driver, $this->annotation->driver, $startTime, microtime(true)));
        } catch (\Throwable $e) {
            $this->dispatcher->dispatch(
                new ProduceFailed(
                    $e,
                    $message,
                    $this->driver,
                    $this->annotation->driver,
                    $startTime,
                    microtime(true)
                )
            );

            throw $e;
        }
    }
}
