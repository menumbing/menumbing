<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Hyperf\Collection\Collection;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\Query\Builder;
use Menumbing\Contract\EventStream\StreamMessage;
use Menumbing\Contract\Outbox\OutboxStatus;
use Menumbing\Outbox\Storage\DatabaseOutboxStorage;
use Menumbing\Serializer\Factory\SerializerFactory;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class DatabaseOutboxStorageTest extends AbstractTestCase
{
    private MockInterface $config;
    private MockInterface $serializerFactory;
    private Serializer $serializer;
    private MockInterface $connectionResolver;
    private MockInterface $connection;
    private DatabaseOutboxStorage $storage;

    protected function setUp(): void
    {
        $this->config = Mockery::mock(ConfigInterface::class);
        $this->config->shouldReceive('get')->with('outbox.storage.connection', 'default')->andReturn('default');
        $this->config->shouldReceive('get')->with('outbox.storage.table', 'outbox_messages')->andReturn('outbox_messages');
        $this->config->shouldReceive('get')->with('outbox.worker.max_retries', 5)->andReturn(3);
        $this->config->shouldReceive('get')->with('event_stream.serialization.serializer', 'default')->andReturn('default');
        $this->config->shouldReceive('get')->with('event_stream.serialization.format', 'json')->andReturn('json');

        $this->serializer = new Serializer(
            [new ObjectNormalizer()],
            [new JsonEncoder()],
        );
        $this->serializerFactory = Mockery::mock(SerializerFactory::class);
        $this->serializerFactory->shouldReceive('get')->with('default')->andReturn($this->serializer);

        $this->connection = Mockery::mock(ConnectionInterface::class);
        $this->connectionResolver = Mockery::mock(ConnectionResolverInterface::class);
        $this->connectionResolver->shouldReceive('connection')->with('default')->andReturn($this->connection);

        $this->storage = new DatabaseOutboxStorage(
            $this->config,
            $this->serializerFactory,
            $this->connectionResolver,
        );
    }

    public function test_store_inserts_correct_fields(): void
    {
        $message = new StreamMessage('user-events', 'user.created', ['id' => 1]);

        $builder = Mockery::mock(Builder::class);
        $this->connection->shouldReceive('table')->with('outbox_messages')->once()->andReturn($builder);

        $builder->shouldReceive('insert')->with(Mockery::on(function (array $data): bool {
            return $data['stream'] === 'user-events'
                && $data['type'] === 'user.created'
                && is_string($data['payload'])
                && str_contains($data['payload'], 'user-events')
                && $data['status'] === 'pending'
                && $data['retry_count'] === 0
                && $data['last_error'] === null
                && $data['sent_at'] === null
                && $data['failed_at'] === null
                && isset($data['id'])
                && isset($data['created_at'])
                && isset($data['updated_at']);
        }))->once();

        $id = $this->storage->store($message);

        $this->assertNotEmpty($id);
    }

    public function test_find_pending_with_for_update_applies_lock_clause(): void
    {
        $builder = $this->expectFindPendingQuery([]);

        $builder->shouldReceive('lock')->with('for update skip locked')->once()->andReturnSelf();

        $records = iterator_to_array($this->storage->findPending(100, 0, true));

        $this->assertEmpty($records);
    }

    public function test_find_pending_without_for_update_does_not_lock(): void
    {
        $builder = $this->expectFindPendingQuery([]);

        $builder->shouldNotReceive('lock');

        $records = iterator_to_array($this->storage->findPending(100, 0, false));

        $this->assertEmpty($records);
    }

    public function test_find_pending_returns_pending_and_failed_messages(): void
    {
        $message1 = new StreamMessage('s1', 't1', null);
        $message2 = new StreamMessage('s2', 't2', null);

        $row1 = (object) [
            'id' => 'uuid-1',
            'payload' => $this->serializer->serialize($message1, 'json'),
            'status' => 'pending',
            'retry_count' => 0,
            'last_error' => null,
            'sent_at' => null,
            'failed_at' => null,
        ];
        $row2 = (object) [
            'id' => 'uuid-2',
            'payload' => $this->serializer->serialize($message2, 'json'),
            'status' => 'failed',
            'retry_count' => 1,
            'last_error' => 'timeout',
            'sent_at' => null,
            'failed_at' => '2024-01-01 00:00:00',
        ];

        $this->expectFindPendingQuery([$row1, $row2]);

        $records = iterator_to_array($this->storage->findPending(100, 0, false));

        $this->assertCount(2, $records);
        $this->assertSame('uuid-1', $records[0]->id);
        $this->assertSame('s1', $records[0]->message->stream);
        $this->assertSame('t1', $records[0]->message->type);
        $this->assertSame(OutboxStatus::PENDING, $records[0]->status);
        $this->assertSame('uuid-2', $records[1]->id);
        $this->assertSame('s2', $records[1]->message->stream);
        $this->assertSame(OutboxStatus::FAILED, $records[1]->status);
        $this->assertSame(1, $records[1]->retryCount);
        $this->assertSame('timeout', $records[1]->lastError);
    }

    public function test_find_pending_applies_retry_after_filter(): void
    {
        $builder = Mockery::mock(Builder::class);
        $this->connection->shouldReceive('table')->with('outbox_messages')->once()->andReturn($builder);

        $builder->shouldReceive('whereIn')->once()->andReturnSelf();
        $builder->shouldReceive('orderBy')->once()->andReturnSelf();
        $builder->shouldReceive('limit')->once()->andReturnSelf();
        $builder->shouldReceive('where')->with(Mockery::type('Closure'))->once()->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn(Collection::make([]));

        $records = iterator_to_array($this->storage->findPending(100, 60, false));

        $this->assertEmpty($records);
    }

    public function test_mark_as_sent_updates_status(): void
    {
        $builder = Mockery::mock(Builder::class);
        $this->connection->shouldReceive('table')->with('outbox_messages')->once()->andReturn($builder);

        $builder->shouldReceive('where')->with('id', 'test-id')->once()->andReturnSelf();
        $builder->shouldReceive('update')->with(Mockery::on(function (array $data): bool {
            return $data['status'] === 'sent'
                && isset($data['sent_at'])
                && isset($data['updated_at']);
        }))->once();

        $this->storage->markAsSent('test-id');
    }

    public function test_mark_as_failed_increments_retry_count(): void
    {
        $existingRecord = (object) ['retry_count' => 1];

        $selectBuilder = Mockery::mock(Builder::class);
        $updateBuilder = Mockery::mock(Builder::class);

        $this->connection->shouldReceive('table')->with('outbox_messages')->twice()
            ->andReturn($selectBuilder, $updateBuilder);

        $selectBuilder->shouldReceive('where')->with('id', 'test-id')->once()->andReturnSelf();
        $selectBuilder->shouldReceive('first')->once()->andReturn($existingRecord);

        $updateBuilder->shouldReceive('where')->with('id', 'test-id')->once()->andReturnSelf();
        $updateBuilder->shouldReceive('update')->with(Mockery::on(function (array $data): bool {
            return $data['status'] === 'failed'
                && $data['retry_count'] === 2
                && isset($data['last_error'])
                && isset($data['failed_at'])
                && isset($data['updated_at']);
        }))->once();

        $this->storage->markAsFailed('test-id', new \RuntimeException('Stream down'));
    }

    public function test_mark_as_failed_transitions_to_dead_letter_after_max_retries(): void
    {
        $existingRecord = (object) ['retry_count' => 2];

        $selectBuilder = Mockery::mock(Builder::class);
        $updateBuilder = Mockery::mock(Builder::class);

        $this->connection->shouldReceive('table')->with('outbox_messages')->twice()
            ->andReturn($selectBuilder, $updateBuilder);

        $selectBuilder->shouldReceive('where')->with('id', 'test-id')->once()->andReturnSelf();
        $selectBuilder->shouldReceive('first')->once()->andReturn($existingRecord);

        $updateBuilder->shouldReceive('where')->with('id', 'test-id')->once()->andReturnSelf();
        $updateBuilder->shouldReceive('update')->with(Mockery::on(function (array $data): bool {
            return $data['status'] === 'dead_letter'
                && $data['retry_count'] === 3;
        }))->once();

        $this->storage->markAsFailed('test-id', new \RuntimeException('Stream down'));
    }

    public function test_mark_as_failed_does_nothing_when_record_not_found(): void
    {
        $selectBuilder = Mockery::mock(Builder::class);

        $this->connection->shouldReceive('table')->with('outbox_messages')->once()->andReturn($selectBuilder);
        $selectBuilder->shouldReceive('where')->with('id', 'missing-id')->once()->andReturnSelf();
        $selectBuilder->shouldReceive('first')->once()->andReturn(null);

        $this->storage->markAsFailed('missing-id', new \RuntimeException('error'));
    }

    public function test_transaction_delegates_to_connection(): void
    {
        $callbackExecuted = false;

        $this->connection->shouldReceive('transaction')->with(Mockery::on(function ($callback) use (&$callbackExecuted): bool {
            $callback();
            return true;
        }))->once();

        $this->storage->transaction(function () use (&$callbackExecuted): void {
            $callbackExecuted = true;
        });

        $this->assertTrue($callbackExecuted);
    }

    public function test_count_pending_returns_count(): void
    {
        $builder = Mockery::mock(Builder::class);
        $this->connection->shouldReceive('table')->with('outbox_messages')->once()->andReturn($builder);

        $builder->shouldReceive('whereIn')->once()->andReturnSelf();
        $builder->shouldReceive('count')->once()->andReturn(42);

        $this->assertSame(42, $this->storage->countPending());
    }

    public function test_prune_deletes_sent_messages_older_than_retention(): void
    {
        $builder = Mockery::mock(Builder::class);
        $this->connection->shouldReceive('table')->with('outbox_messages')->once()->andReturn($builder);

        $builder->shouldReceive('where')->with('status', 'sent')->once()->andReturnSelf();
        $builder->shouldReceive('where')->with('sent_at', '<', Mockery::type('string'))->once()->andReturnSelf();
        $builder->shouldReceive('delete')->once()->andReturn(15);

        $this->assertSame(15, $this->storage->prune(7));
    }

    /**
     * Set up the mock chain for findPending query builder (without retry_after filter).
     */
    private function expectFindPendingQuery(array $rows): MockInterface
    {
        $builder = Mockery::mock(Builder::class);

        $this->connection->shouldReceive('table')->with('outbox_messages')->once()->andReturn($builder);
        $builder->shouldReceive('whereIn')->once()->andReturnSelf();
        $builder->shouldReceive('orderBy')->once()->andReturnSelf();
        $builder->shouldReceive('limit')->once()->andReturnSelf();
        $builder->shouldReceive('get')->once()->andReturn(Collection::make($rows));

        return $builder;
    }
}
