<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Handler;

use Menumbing\Contract\EventStream\StreamInterface;
use Menumbing\Contract\EventStream\StreamMessage;
use Menumbing\EventStream\Annotation\ProducedEvent;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ProduceEventHandler
{
    public function __construct(protected StreamInterface $driver, protected ProducedEvent $annotation)
    {
    }

    public function __invoke(object $event): void
    {
        $this->driver->publish(new StreamMessage(
            stream: $this->annotation->stream,
            type: $this->annotation->name,
            data: $event,
        ));
    }
}
