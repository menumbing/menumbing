<?php

declare(strict_types=1);

namespace Menumbing\Outbox\Storage;

use Generator;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\ConnectionResolverInterface;
use Menumbing\Contract\EventStream\StreamMessage;
use Menumbing\Contract\Outbox\OutboxRecord;
use Menumbing\Contract\Outbox\OutboxStatus;
use Menumbing\Contract\Outbox\OutboxStorageInterface;
use Menumbing\Serializer\Factory\SerializerFactory;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Serializer\Serializer;
use Throwable;

use function Hyperf\Support\now;

/**
 * Database-backed outbox storage.
 *
 * Uses the same database connection as the ORM so that {@see store()}
 * participates in the active database transaction, guaranteeing
 * atomicity between business data and the outbox record.
 *
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class DatabaseOutboxStorage implements OutboxStorageInterface
{
    protected string $connectionName;
    protected string $table;
    protected int $maxRetries;

    protected Serializer $serializer;
    protected string $serializeFormat;

    public function __construct(
        ConfigInterface $config,
        SerializerFactory $serializerFactory,
        protected ConnectionResolverInterface $connectionResolver,
    ) {
        $this->connectionName = $config->get('outbox.storage.connection', 'default');
        $this->table = $config->get('outbox.storage.table', 'outbox_messages');
        $this->maxRetries = $config->get('outbox.worker.max_retries', 5);

        $this->serializer = $serializerFactory->get(
            $config->get('event_stream.serialization.serializer', 'default')
        );
        $this->serializeFormat = $config->get('event_stream.serialization.format', 'json');
    }

    public function store(StreamMessage $message): string
    {
        $id = Uuid::uuid4()->toString();
        $now = now()->toDateTimeString();

        $this->connection()->table($this->table)->insert([
            'id' => $id,
            'stream' => $message->stream,
            'type' => $message->type,
            'payload' => $this->serializer->serialize($message, $this->serializeFormat),
            'status' => OutboxStatus::PENDING->value,
            'retry_count' => 0,
            'last_error' => null,
            'sent_at' => null,
            'failed_at' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $id;
    }

    public function findPending(int $limit, int $retryAfter = 0, bool $forUpdate = false): Generator
    {
        $query = $this->connection()->table($this->table)
            ->whereIn('status', [OutboxStatus::PENDING->value, OutboxStatus::FAILED->value])
            ->orderBy('created_at', 'asc')
            ->limit($limit);

        if ($retryAfter > 0) {
            $cutoff = now()->subSeconds($retryAfter)->toDateTimeString();

            $query->where(function ($q) use ($cutoff) {
                $q->whereNull('failed_at')
                    ->orWhere('failed_at', '<=', $cutoff);
            });
        }

        if ($forUpdate) {
            $query->lock('for update skip locked');
        }

        foreach ($query->get() as $row) {
            yield $this->toRecord($row);
        }
    }

    public function transaction(callable $callback): void
    {
        $this->connection()->transaction($callback);
    }

    public function markAsSent(string $id): void
    {
        $now = now()->toDateTimeString();

        $this->connection()->table($this->table)
            ->where('id', $id)
            ->update([
                'status' => OutboxStatus::SENT->value,
                'sent_at' => $now,
                'updated_at' => $now,
            ]);
    }

    public function markAsFailed(string $id, Throwable $error): void
    {
        $record = $this->connection()->table($this->table)->where('id', $id)->first();

        if (!$record) {
            return;
        }

        $retryCount = (int) $record->retry_count + 1;
        $now = now()->toDateTimeString();

        $status = $retryCount >= $this->maxRetries
            ? OutboxStatus::DEAD_LETTER->value
            : OutboxStatus::FAILED->value;

        $this->connection()->table($this->table)
            ->where('id', $id)
            ->update([
                'status' => $status,
                'retry_count' => $retryCount,
                'last_error' => mb_substr($error->getMessage(), 0, 65535),
                'failed_at' => $now,
                'updated_at' => $now,
            ]);
    }

    public function countPending(): int
    {
        return $this->connection()->table($this->table)
            ->whereIn('status', [OutboxStatus::PENDING->value, OutboxStatus::FAILED->value])
            ->count();
    }

    public function prune(int $retentionDays): int
    {
        $cutoff = now()->subDays($retentionDays)->toDateTimeString();

        return $this->connection()->table($this->table)
            ->where('status', OutboxStatus::SENT->value)
            ->where('sent_at', '<', $cutoff)
            ->delete();
    }

    protected function connection(): ConnectionInterface
    {
        return $this->connectionResolver->connection($this->connectionName);
    }

    protected function toRecord(object|array $row): OutboxRecord
    {
        $row = (object) $row;

        $message = $this->serializer->deserialize(
            $row->payload,
            StreamMessage::class,
            $this->serializeFormat
        );

        return new OutboxRecord(
            id: $row->id,
            message: $message,
            status: OutboxStatus::from($row->status),
            retryCount: (int) $row->retry_count,
            lastError: $row->last_error,
            sentAt: $row->sent_at,
            failedAt: $row->failed_at,
        );
    }
}
