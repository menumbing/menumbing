<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Consumer;

use Generator;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Coordinator\Constants;
use Hyperf\Coordinator\CoordinatorManager;
use Hyperf\Coroutine\Coroutine;
use Hyperf\Engine\Channel;
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
use Throwable;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
abstract class ConsumerProcess extends AbstractProcess
{
    public ?string $streamName = null;

    public string $driverName = 'default';

    public ?string $groupName = null;

    public int $concurrent = 1;

    protected int $restartInterval = 1;

    protected StreamInterface $stream;

    protected EventRegistry $eventRegistry;

    protected ConfigInterface $config;

    protected ?string $consumerName = null;

    protected array $retryCounters = [];

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
        } catch (Throwable $e) {
            $this->event?->dispatch(new ConsumerGroupCreateFailed($consumerName, $this->groupName, $this->streamName, $this->driverName, $e));
            $this->logThrowable($e);

            return;
        }

        $this->event?->dispatch(new ConsumerStarted($consumerName, $this->groupName, $this->streamName, $this->driverName));

        // Set instance ID for Kafka static membership (deterministic group_instance_id).
        // This must happen before subscribe() is called so the consumer factory can use it.
        if (method_exists($this->stream, 'setConsumerName')) {
            $this->stream->setConsumerName($this->getInstanceId());
        }

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

                if ($this->concurrent <= 1) {
                    foreach ($messages as $message)  {
                        $this->processMessage($message);
                    }
                } else {
                    $this->processBatch($messages);
                }
            } catch (Throwable $e) {
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

        $retryCount = $this->retryCounters[$message->id] ?? 0;
        $message = $message->withContext([...$message->context, 'retry_count' => $retryCount]);

        $this->event?->dispatch(new BeforeConsume($consumerName, $this->groupName, $message, $this->stream, $this->driverName));

        try {
            $result = call_user_func($this->handler(), $this->groupName, $message);

            if (Result::ACK === $result) {
                unset($this->retryCounters[$message->id]);
                $this->stream->ack($this->groupName, $this->streamName, [$message->id]);

                $this->event?->dispatch(new AfterConsume($consumerName, $this->groupName, $message, $this->stream, $this->driverName));
            } elseif (Result::NACK === $result) {
                $this->retryCounters[$message->id] = $retryCount + 1;
            }
        } catch (Throwable $e) {
            if ($this->skipException($e)) {
                unset($this->retryCounters[$message->id]);
                $this->stream->ack($this->groupName, $this->streamName, [$message->id]);
            }

            $this->event?->dispatch(new ConsumeFailed($consumerName, $this->groupName, $message, $this->stream, $this->driverName, $e));
        }
    }

    protected function processBatch(Generator $messages): void
    {
        $batch = [];
        foreach ($messages as $message) {
            $batch[] = $message;
        }

        if (empty($batch)) {
            return;
        }

        $channel = new Channel(count($batch));

        foreach ($batch as $message) {
            Coroutine::create(function () use ($message, $channel) {
                try {
                    $this->processMessage($message);
                } finally {
                    $channel->push(true);
                }
            });
        }

        for ($i = 0; $i < count($batch); $i++) {
            $channel->pop();
        }
    }

    protected function getConsumerName(): string
    {
        return $this->consumerName ??= sprintf('%s-%s', $this->streamName, gethostname());
    }

    /**
     * Derive a deterministic instance ID for Kafka static membership.
     * Combines process name (which includes process index when nums > 1) with hostname,
     * making it unique per pod+process while remaining stable across pod restarts.
     */
    protected function getInstanceId(): string
    {
        // $this->name = e.g. "default:loan-events" or "default:loan-repayment-events.1"
        // Convert to kebab-case: replace colons and underscores with hyphens
        $processName = str_replace([':', '_'], '-', $this->name);

        return $processName . '-' . gethostname();
    }

    protected function skipException(Throwable $e): bool
    {
        $skipExceptions = $this->config->get('event_stream.skip_exceptions', []);

        if (empty($skipExceptions)) {
            return false;
        }

        foreach ($skipExceptions as $skipException) {
            if (is_a($e, $skipException, true)
                || is_subclass_of($e, $skipException)
                || in_array($skipException, class_implements($e), true)) {
                return true;
            }
        }

        return false;
    }

    abstract protected function handler(): callable;
}
