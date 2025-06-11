<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Listener;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Menumbing\EventStream\Event\AfterConsume;
use Menumbing\EventStream\Event\BeforeConsume;
use Menumbing\EventStream\Event\ConsumeEvent;
use Menumbing\EventStream\Event\ConsumeFailed;
use Menumbing\EventStream\Event\ConsumerEvent;
use Menumbing\EventStream\Event\ConsumerGroupCreated;
use Menumbing\EventStream\Event\ConsumerGroupCreateFailed;
use Menumbing\EventStream\Event\ConsumerStarted;
use Menumbing\EventStream\Event\ConsumerStopped;
use Menumbing\EventStream\Event\SubscribeFailed;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
final class DebugListener implements ListenerInterface
{
    public function __construct(
        private StdoutLoggerInterface $logger,
        private ConfigInterface $config,
    ) {
    }

    public function listen(): array
    {
        return [
            AfterConsume::class,
            BeforeConsume::class,
            ConsumeFailed::class,
            ConsumerGroupCreated::class,
            ConsumerGroupCreateFailed::class,
            ConsumerStarted::class,
            ConsumerStopped::class,
            SubscribeFailed::class,
        ];
    }

    public function process(object $event): void
    {
        if (!$this->config->get('app_debug', 'prod' !== $this->config->get('app_env', 'prod'))) {
            return;
        }

        switch (true) {
            case $event instanceof BeforeConsume:
                $this->debug('Processing', $event);
                break;
            case $event instanceof AfterConsume:
                $this->debug('Processed', $event);
                break;
            case $event instanceof ConsumeFailed:
                $this->debug('Failed Process', $event);
                break;
            case $event instanceof ConsumerGroupCreated:
                $this->debug('Consumer Group Created', $event);
                break;
            case $event instanceof ConsumerGroupCreateFailed:
                $this->debug('Consumer Group Create Failed', $event);
                break;
            case $event instanceof ConsumerStarted:
                $this->debug('Consumer Started', $event);
                break;
            case $event instanceof ConsumerStopped:
                $this->debug('Consumer Stopped', $event);
                break;
            case $event instanceof SubscribeFailed:
                $this->debug('Subscribe Failed', $event);
                break;
        }
    }

    protected function debug(string $type, object $event): void
    {
        if ($event instanceof ConsumeEvent) {
            $this->logger->debug(
                message: '[{timestamp}] Event Stream {type} event {message_id} from stream {stream_name} on group {group_name} with driver {driver_name}',
                context: [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'type' => $type,
                    'message_id' => $event->message->id,
                    'stream_name' => $event->message->stream,
                    'group_name' => $event->groupName,
                    'driver_name' => $event->streamDriver,
                ]
            );
        }

        if ($event instanceof ConsumerEvent) {
            $this->logger->debug(
                message: '[{timestamp}] Event Stream {type} on group {group_name} with driver {driver_name}',
                context: [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'type' => $type,
                    'group_name' => $event->groupName,
                    'driver_name' => $event->streamDriver,
                ]
            );
        }
    }
}
