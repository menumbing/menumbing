<?php

declare(strict_types=1);

namespace Menumbing\Contract\EventStream;

use Generator;

/**
 * Interface for interacting with a stream driver.
 */
interface StreamInterface
{
    /**
     * Creates a new group associated with a specific stream if not exists.
     *
     * @param  string  $name  The name of the group to be created.
     * @param  string  $stream  The name of the stream associated with the group.
     *
     * @return bool Returns true if the group was successfully created, false otherwise.
     */
    public function createGroup(string $name, string $stream): bool;

    /**
     * Publishes a message to the specified stream.
     *
     * @param  StreamMessage  $message  The message to be published to the stream.
     *
     * @return string Returns a unique identifier for the published message.
     */
    public function publish(StreamMessage $message): string;

    /**
     * Subscribes a consumer to a group and listens to multiple streams.
     *
     * @param  string  $consumer  The name of the consumer subscribing to the group.
     * @param  string  $group  The name of the group to which the consumer is subscribing.
     * @param  array  $streams  An array of stream names that the consumer should listen to.
     *
     * @return Generator<StreamMessage> A generator that yields messages from the subscribed streams.
     */
    public function subscribe(string $consumer, string $group, array $streams): Generator;

    /**
     * Acknowledges one or more messages in the given stream and group.
     *
     * @param  string  $group  The name of the consumer group.
     * @param  string  $stream  The name of the stream.
     * @param  array  $ids  An array of message IDs to acknowledge.
     *
     * @return bool Returns true on success, or false on failure.
     */
    public function ack(string $group, string $stream, array $ids): bool;
}
