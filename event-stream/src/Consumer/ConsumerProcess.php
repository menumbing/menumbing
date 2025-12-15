<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Consumer;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Coordinator\Constants;
use Hyperf\Coordinator\CoordinatorManager;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\ProcessManager;
use Menumbing\Contract\EventStream\StreamInterface;
use Menumbing\Contract\EventStream\StreamMessage;
use Menumbing\EventStream\Enum\Result;
use Menumbing\EventStream\Event\AfterConsume;
use Menumbing\EventStream\Event\BeforeConsume;
use Menumbing\EventStream\Event\ConsumeFailed;
use Menumbing\EventStream\Event\ConsumerGroupCreated;
use Menumbing\EventStream\Event\ConsumerGroupCreateFailed;
use Menumbing\EventStream\Event\ConsumerStarted;
use Menumbing\EventStream\Event\ConsumerStopped;
use Menumbing\EventStream\Event\SubscribeFailed;
use Menumbing\EventStream\EventRegistry;
use Menumbing\EventStream\Factory\StreamFactory;
use Psr\Container\ContainerInterface;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
abstract class ConsumerProcess extends AbstractProcess
{
    public ?string $streamName = null;

    public string $driverName = 'default';

    public ?string $groupName = null;

    protected int $restartInterval = 1;

    protected StreamInterface $stream;

    protected EventRegistry $eventRegistry;

    protected ConfigInterface $config;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->stream = $container->get(StreamFactory::class)->get($this->driverName);
        $this->eventRegistry = $container->get(EventRegistry::class);
        $this->config = $container->get(ConfigInterface::class);
    }

    public function handle(): void
    {
        $consumerName = $this->getConsumerName();

        try {
            $this->stream->createGroup($this->groupName, $this->streamName);
            $this->event?->dispatch(new ConsumerGroupCreated($consumerName, $this->groupName, $this->streamName, $this->driverName));
        } catch (\Throwable $e) {
            $this->event?->dispatch(new ConsumerGroupCreateFailed($consumerName, $this->groupName, $this->streamName, $this->driverName, $e));
            $this->logThrowable($e);

            return;
        }

        $this->event?->dispatch(new ConsumerStarted($consumerName, $this->groupName, $this->streamName, $this->driverName));

        Coroutine::create(function () use ($consumerName) {
            $retryAfter = $this->config->get('event_stream.consumer.retry_after', 60) * 1000;

            while (ProcessManager::isRunning()) {
                $messages = $this->stream->getIdleMessages($consumerName, $this->groupName, [$this->streamName], $retryAfter);

                foreach ($messages as $message) {
                    $this->processMessage($message);
                }

                if (CoordinatorManager::until(Constants::WORKER_EXIT)->yield($this->config->get('event_stream.consumer.block_for', 1))) {
                    break;
                }
            }
        });

        while (ProcessManager::isRunning()) {
            try {
                $messages = $this->stream->subscribe($consumerName, $this->groupName, [$this->streamName]);

                foreach ($messages as $message)  {
                    $this->processMessage($message);
                }
            } catch (\Throwable $e) {
                $this->event?->dispatch(new SubscribeFailed($consumerName, $this->groupName, $this->streamName, $this->driverName, $e));
                $this->logThrowable($e);
            }

            if (CoordinatorManager::until(Constants::WORKER_EXIT)->yield($this->config->get('event_stream.consumer.block_for', 1))) {
                $this->event?->dispatch(new ConsumerStopped($consumerName, $this->groupName, $this->streamName, $this->driverName));
                break;
            }
        }
    }

    protected function processMessage(StreamMessage $message): void
    {
        $consumerName = $this->getConsumerName();

        $this->event?->dispatch(new BeforeConsume($consumerName, $this->groupName, $message, $this->stream, $this->driverName));

        try {
            $result = call_user_func($this->handler(), $this->groupName, $message);

            if (Result::ACK === $result) {
                $this->stream->ack($this->groupName, $this->streamName, [$message->id]);

                $this->event?->dispatch(new AfterConsume($consumerName, $this->groupName, $message, $this->stream, $this->driverName));
            }
        } catch (\Throwable $e) {
            $this->event?->dispatch(new ConsumeFailed($consumerName, $this->groupName, $message, $this->stream, $this->driverName, $e));
        }
    }

    protected function getConsumerName(): string
    {
        return sprintf('%s-%s', $this->streamName, gethostname());
    }

    abstract protected function handler(): callable;
}
