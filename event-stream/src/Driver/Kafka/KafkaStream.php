<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Driver\Kafka;

use Generator;
use Hyperf\Kafka\Producer;
use Hyperf\Kafka\ProducerManager;
use longlang\phpkafka\Consumer\ConsumeMessage;
use longlang\phpkafka\Consumer\Consumer;
use Menumbing\Contract\EventStream\IdProviderInterface;
use Menumbing\Contract\EventStream\StreamInterface;
use Menumbing\Contract\EventStream\StreamMessage;
use Swoole\Coroutine;
use Symfony\Component\Serializer\Serializer;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class KafkaStream implements StreamInterface
{
    protected Producer $producer;

    /**
     * @var ConsumeMessage[]
     */
    protected array $pendingMessages = [];

    public function __construct(
        ProducerManager $producerManager,
        protected ConsumerFactory $consumerFactory,
        protected Serializer $serializer,
        protected ?IdProviderInterface $idProvider = null,
        protected array $options = [],
    ) {
        $this->producer = $producerManager->getProducer($this->options['pool'] ?? 'default');
        $this->id = $this->id ?? new DefaultKafkaId();
    }

    public function createGroup(string $name, string $stream): bool
    {
        return true;
    }

    public function publish(StreamMessage $message): string
    {
        $stream = $message->stream;
        $messageId = $messageId ?? $this->id->newId();

        $this->producer->send(
            $stream,
            $this->serializer->serialize($message, $this->options['serialize_format']),
            $messageId
        );

        return $messageId;
    }

    public function subscribe(string $consumer, string $group, array $streams): Generator
    {
        $consumer = $this->getConsumer($group, $streams);

        $waitTimeout = $this->options['wait_time'] ?? 100;
        $start = microtime(true);

        while (true) {
            if (null !== $message = $consumer->consume()) {
                break;
            }

            $elapsed = (microtime(true) - $start) * 1000;

            if ($elapsed >= $waitTimeout) {
                break;
            }

            Coroutine::sleep(0.005);
        }

        if (null !== $message) {
            $key = $message->getTopic().'.'.$message->getKey();

            $this->pendingMessages[$key] = $message;

            yield $message->getTopic() => $this->deserialize($message->getKey(), ['message' => $message->getValue()]);
        }
    }

    public function getIdleMessages(string $consumer, string $group, array $streams, int $retryAfter): Generator
    {
        return [];
    }

    public function ack(string $group, string $stream, array $ids): bool
    {
        $consumer = $this->getConsumer($group, [$stream]);
        $count = 0;

        foreach ($ids as $id) {
            $key = $stream.'.'.$id;
            if (null !== $message = $this->pendingMessages[$key]) {
                $consumer->ack($message);
                ++$count;
            }
        }

        return count($ids) === $count;
    }

    protected function deserialize(string $id, array $message, int $retryCount = 0): StreamMessage
    {
        $streamMessage = $this->serializer->deserialize($message['message'], StreamMessage::class, $this->options['deserialize_format'] ?? 'json');
        $streamMessage = $streamMessage->withContext(['retry_count' => $retryCount]);

        return $streamMessage->withId($id);
    }

    protected function getConsumer(string $group, array $streams): Consumer
    {
        return $this->consumerFactory->get(
            $this->options['pool'] ?? 'default',
            [
                ...$this->options,
                'group_id' => $group,
                'topic'    => $streams,
            ]
        );
    }
}
