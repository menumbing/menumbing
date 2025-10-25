<?php

declare(strict_types=1);

namespace Menumbing\EventStream\Driver\Kafka;

use Hyperf\Contract\ConfigInterface;
use longlang\phpkafka\Client\SwooleClient;
use longlang\phpkafka\Consumer\Consumer;
use longlang\phpkafka\Consumer\ConsumerConfig;
use longlang\phpkafka\Socket\SwooleSocket;
use longlang\phpkafka\Timer\SwooleTimer;
use RuntimeException;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class ConsumerFactory
{
    /**
     * @var Consumer[]
     */
    protected array $consumers = [];

    public function __construct(protected ConfigInterface $config)
    {
    }

    public function get(string $poolName, array $options): Consumer
    {
        if (null !== $consumer = $this->consumers[$poolName] ?? null) {
            return $consumer;
        }

        $consumer = new Consumer($this->getConfig($poolName, $options));

        $this->consumers[$poolName] = $consumer;

        return $consumer;
    }

    protected function getConfig(string $poolName, array $options): ConsumerConfig
    {
        if (null === $config =  $this->config->get('kafka.' . $poolName)) {
            throw new RuntimeException(sprintf('Kafka pool "%s" is not defined.', $poolName));
        }

        $consumerConfig = new ConsumerConfig();
        $consumerConfig->setAutoCommit($options['auto_commit'] ?? true);
        $consumerConfig->setRackId($config['rack_id']);
        $consumerConfig->setReplicaId($config['replica_id']);
        $consumerConfig->setTopic($options['topic']);
        $consumerConfig->setRebalanceTimeout($config['rebalance_timeout']);
        $consumerConfig->setSendTimeout($config['send_timeout']);
        $consumerConfig->setGroupId($options['group_id'] ?? uniqid('hyperf-kafka-'));
        $consumerConfig->setGroupInstanceId(sprintf('%s-%s', $options['group_id'], uniqid()));
        $consumerConfig->setMemberId($options['member_id'] ?: '');
        $consumerConfig->setInterval($config['interval']);
        $consumerConfig->setBootstrapServers($config['bootstrap_servers']);
        $consumerConfig->setClient($config['client'] ?? SwooleClient::class);
        $consumerConfig->setSocket($config['socket'] ?? SwooleSocket::class);
        $consumerConfig->setTimer($config['timer'] ?? SwooleTimer::class);
        $consumerConfig->setMaxWriteAttempts($config['max_write_attempts']);
        $consumerConfig->setClientId(sprintf('%s-%s', $config['client_id'] ?: 'Hyperf', uniqid()));
        $consumerConfig->setRecvTimeout($config['recv_timeout']);
        $consumerConfig->setConnectTimeout($config['connect_timeout']);
        $consumerConfig->setSessionTimeout($config['session_timeout']);
        $consumerConfig->setGroupRetry($config['group_retry']);
        $consumerConfig->setGroupRetrySleep($config['group_retry_sleep']);
        $consumerConfig->setGroupHeartbeat($config['group_heartbeat']);
        $consumerConfig->setOffsetRetry($config['offset_retry']);
        $consumerConfig->setAutoCreateTopic($config['auto_create_topic']);
        $consumerConfig->setPartitionAssignmentStrategy($config['partition_assignment_strategy']);
        ! empty($config['sasl']) && $consumerConfig->setSasl($config['sasl']);
        ! empty($config['ssl']) && $consumerConfig->setSsl($config['ssl']);
        is_callable($config['exception_callback'] ?? null) && $consumerConfig->setExceptionCallback($config['exception_callback']);

        return $consumerConfig;
    }
}
