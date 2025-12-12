<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Listener;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Redis\Event\CommandExecuted;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SpanTagManager;
use Hyperf\Tracer\SwitchManager;

use const OpenTracing\Tags\SPAN_KIND_RPC_CLIENT;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class RedisCommandExecutedListener implements ListenerInterface
{
    use SpanStarter;

    public function __construct(
        private readonly SwitchManager $switchManager,
        private readonly SpanTagManager $spanTagManager,
        private readonly ConfigInterface $config,
    )
    {
    }

    public function listen(): array
    {
        return [
            CommandExecuted::class,
        ];
    }

    /**
     * @param  CommandExecuted  $event
     *
     * @return void
     */
    public function process(object $event): void
    {
        if ($this->switchManager->isEnable('redis_call') === false) {
            return;
        }

        $redisConfig = $this->getRedisConfig($event->connectionName);

        $endTime = microtime(true);
        $span = $this->startSpan('redis.' . $event->command , [
            'start_time' => (int) (($endTime - $event->time / 1000) * 1000 * 1000),
        ], kind: SPAN_KIND_RPC_CLIENT);

        $span->setTag('db.system', 'redis');
        $span->setTag('db.operation', $event->command);
        $span->setTag('db.connection', $event->connectionName ?? 'default');
        $span->setTag('db.name', $redisConfig['db'] ?? 0);
        $span->setTag('server.address', $redisConfig['host'] ?? 'localhost');
        $span->setTag('server.port', $redisConfig['port'] ?? 6379);

        $span->setTag('redis.arguments', json_encode($event->parameters));
        $span->setTag('redis.result', is_scalar($event->result) ? $event->result : json_encode($event->result));

        $span->finish((int) ($endTime * 1000 * 1000));
    }

    protected function getRedisConfig(string $pool): array
    {
        return $this->config->get('redis.' . $pool, []);
    }
}
