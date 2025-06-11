<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Handler;

use Menumbing\Contract\EventStream\StreamMessage;
use Menumbing\EventStream\Enum\Result;
use Menumbing\EventStream\EventRegistry;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Serializer\Serializer;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ConsumerEventHandler
{
    public function __construct(
        protected EventDispatcherInterface $event,
        protected EventRegistry $eventRegistry,
        protected Serializer $serializer,
    ) {
    }

    public function __invoke(string $groupName, StreamMessage $message): Result
    {
        $metadata = $this->eventRegistry->getClassByName($message->type);

        try {
            $this->event->dispatch(
                $this->serializer->denormalize($message->data, $metadata['class'])
            );

            return Result::ACK;
        } catch (\Throwable $e) {
            $retryCount = $message->context['retry_count'] ?? 0;

            if ($retryCount >= $metadata['retries']) {
                throw $e;
            }

            return Result::NACK;
        }
    }
}
