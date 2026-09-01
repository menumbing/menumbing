<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Hyperf\Contract\ConfigInterface;
use Mockery;
use Menumbing\Contract\EventStream\StreamInterface;
use Menumbing\Contract\EventStream\StreamMessage;
use Menumbing\Contract\Outbox\OutboxRecord;
use Menumbing\Contract\Outbox\OutboxStatus;
use Menumbing\Contract\Outbox\OutboxStorageInterface;
use Menumbing\Outbox\Event\OutboxDeadLettered;
use Menumbing\Outbox\Event\OutboxRelayed;
use Menumbing\Outbox\Event\OutboxRelayFailed;
use Menumbing\Outbox\Process\OutboxRelayProcess;
use Mockery\MockInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use RuntimeException;

class OutboxRelayProcessTest extends AbstractTestCase
{
    private MockInterface $container;
    private MockInterface $storage;
    private MockInterface $stream;
    private MockInterface $config;
    private MockInterface $dispatcher;
    private TestableOutboxRelayProcess $process;

    protected function setUp(): void
    {
        $this->storage = Mockery::mock(OutboxStorageInterface::class);
        $this->stream = Mockery::mock(StreamInterface::class);
        $this->config = Mockery::mock(ConfigInterface::class);
        $this->dispatcher = Mockery::mock(EventDispatcherInterface::class);

        $this->container = Mockery::mock(ContainerInterface::class);
        $this->container->allows()->has(EventDispatcherInterface::class)->andReturns(true);
        $this->container->allows()->get(OutboxStorageInterface::class)->andReturns($this->storage);
        $this->container->allows()->get(StreamInterface::class)->andReturns($this->stream);
        $this->container->allows()->get(ConfigInterface::class)->andReturns($this->config);
        $this->container->allows()->get(EventDispatcherInterface::class)->andReturns($this->dispatcher);

        $this->process = new TestableOutboxRelayProcess($this->container);
    }

    public function test_relay_marks_as_sent_on_success(): void
    {
        $record = $this->createRecord('uuid-1', OutboxStatus::PENDING, 0);

        $this->stream->expects()->publish($record->message)
            ->andReturns('stream-id-1');

        $this->storage->expects()->markAsSent('uuid-1');

        $this->dispatcher->expects()->dispatch(Mockery::on(function ($event): bool {
            return $event instanceof OutboxRelayed
                && $event->record->id === 'uuid-1';
        }));

        $this->process->exposeRelay($record, 5);
    }

    public function test_relay_marks_as_failed_on_exception(): void
    {
        $record = $this->createRecord('uuid-2', OutboxStatus::FAILED, 1);
        $exception = new RuntimeException('Stream unavailable');

        $this->stream->expects()->publish($record->message)
            ->andThrows($exception);

        $this->storage->expects()->markAsFailed('uuid-2', $exception);

        $this->dispatcher->expects()->dispatch(Mockery::on(function ($event) use ($exception): bool {
            return $event instanceof OutboxRelayFailed
                && $event->record->id === 'uuid-2'
                && $event->throwable === $exception;
        }));

        $this->process->exposeRelay($record, 5);
    }

    public function test_relay_dispatches_dead_lettered_after_max_retries(): void
    {
        $record = $this->createRecord('uuid-3', OutboxStatus::FAILED, 4);
        $exception = new RuntimeException('Stream still down');

        $this->stream->expects()->publish($record->message)
            ->andThrows($exception);

        $this->storage->expects()->markAsFailed('uuid-3', $exception);

        $this->dispatcher->expects()->dispatch(Mockery::on(function ($event) use ($exception): bool {
            return $event instanceof OutboxDeadLettered
                && $event->record->id === 'uuid-3'
                && $event->throwable === $exception;
        }));

        // retryCount (4) + 1 = 5, which equals maxRetries (5) → dead-lettered
        $this->process->exposeRelay($record, 5);
    }

    public function test_relay_does_not_dead_letter_when_below_max_retries(): void
    {
        $record = $this->createRecord('uuid-4', OutboxStatus::FAILED, 3);
        $exception = new RuntimeException('Timeout');

        $this->stream->expects()->publish($record->message)
            ->andThrows($exception);

        $this->storage->expects()->markAsFailed('uuid-4', $exception);

        // Should dispatch OutboxRelayFailed, NOT OutboxDeadLettered
        $this->dispatcher->expects()->dispatch(Mockery::on(function ($event): bool {
            return $event instanceof OutboxRelayFailed;
        }));

        $this->dispatcher->shouldNotReceive('dispatch')
            ->with(Mockery::on(fn ($e) => $e instanceof OutboxDeadLettered));

        // retryCount (3) + 1 = 4, which is less than maxRetries (5) → not dead-lettered
        $this->process->exposeRelay($record, 5);
    }

    private function createRecord(
        string $id,
        OutboxStatus $status,
        int $retryCount,
    ): OutboxRecord {
        return new OutboxRecord(
            id: $id,
            message: new StreamMessage('test-stream', 'test.event', ['key' => 'value']),
            status: $status,
            retryCount: $retryCount,
        );
    }
}

/**
 * Test subclass that exposes the protected relay() method.
 */
class TestableOutboxRelayProcess extends OutboxRelayProcess
{
    public function exposeRelay(OutboxRecord $record, int $maxRetries): void
    {
        $this->relay($record, $maxRetries);
    }
}
