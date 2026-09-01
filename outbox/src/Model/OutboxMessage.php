<?php

declare(strict_types=1);

namespace Menumbing\Outbox\Model;

use Menumbing\Orm\Model;

/**
 * Eloquent model for the outbox_messages table.
 *
 * This model is provided primarily for querying and monitoring purposes.
 * The {@see \Menumbing\Outbox\Storage\DatabaseOutboxStorage} uses the
 * query builder directly to ensure it participates in the active
 * database transaction.
 *
 * @author  Aldi Arief <aldiarief598@gmail.com>
 */
class OutboxMessage extends Model
{
    protected ?string $table = 'outbox_messages';

    public bool $incrementing = false;

    protected string $keyType = 'string';

    protected array $fillable = [
        'id',
        'stream',
        'type',
        'payload',
        'status',
        'retry_count',
        'last_error',
        'sent_at',
        'failed_at',
    ];

    protected array $casts = [
        'payload' => 'json',
        'retry_count' => 'integer',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];
}
