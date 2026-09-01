<?php

declare(strict_types=1);

namespace Menumbing\Contract\Outbox;

use Generator;
use Menumbing\Contract\EventStream\StreamMessage;
use Throwable;

/**
 * Abstraction for storing and retrieving outbox messages.
 *
 * Implementations MUST ensure that {@see store()} participates in the
 * currently active database transaction so the outbox record commits
 * atomically with the business data.
 *
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
interface OutboxStorageInterface
{
    /**
     * Persist a stream message to the outbox.
     *
     * This method is expected to run within the caller's database
     * transaction. If the transaction rolls back the outbox record
     * MUST be rolled back as well.
     *
     * @param  StreamMessage  $message  The message to store.
     *
     * @return string The generated outbox record ID.
     */
    public function store(StreamMessage $message): string;

    /**
     * Retrieve messages that are ready for relay.
     *
     * Returns messages whose status is PENDING or FAILED, excluding
     * FAILED messages that were last attempted within the retry window.
     *
     * When $forUpdate is true, the implementation SHOULD lock the selected
     * rows (e.g., FOR UPDATE SKIP LOCKED) to prevent concurrent workers
     * from processing the same messages. The caller MUST wrap the call
     * in {@see transaction()} when $forUpdate is true so that the locks
     * are held until the status updates are committed.
     *
     * @param  int   $limit       Maximum number of messages to return.
     * @param  int   $retryAfter   Minimum seconds to wait before retrying a
     *                            failed message. Zero means no delay.
     * @param  bool  $forUpdate   Whether to lock the rows for update.
     *
     * @return Generator<OutboxRecord>
     */
    public function findPending(int $limit, int $retryAfter = 0, bool $forUpdate = false): Generator;

    /**
     * Execute the given callback within a storage transaction.
     *
     * When using FOR UPDATE locking in {@see findPending()}, this method
     * MUST be used to wrap the query and subsequent status updates so
     * that the locks are held until the transaction commits.
     *
     * @param  callable  $callback
     */
    public function transaction(callable $callback): void;

    /**
     * Mark a message as successfully relayed to the stream.
     *
     * @param  string  $id  The outbox record ID.
     */
    public function markAsSent(string $id): void;

    /**
     * Mark a message as failed.
     *
     * Increments the retry count and records the error. If the retry
     * count reaches the configured maximum the message SHOULD be
     * transitioned to DEAD_LETTER status.
     *
     * @param  string     $id     The outbox record ID.
     * @param  Throwable  $error  The exception that caused the failure.
     */
    public function markAsFailed(string $id, Throwable $error): void;

    /**
     * Count messages that are currently awaiting relay.
     *
     * @return int
     */
    public function countPending(): int;

    /**
     * Delete sent messages older than the given retention period.
     *
     * Messages with status SENT and sent_at older than the cutoff
     * are permanently deleted. This is intended for periodic cleanup
     * via a scheduled command, not by the relay worker.
     *
     * @param  int  $retentionDays  Number of days to retain sent messages.
     *
     * @return int The number of deleted messages.
     */
    public function prune(int $retentionDays): int;
}
