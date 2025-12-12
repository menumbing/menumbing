<?php

declare(strict_types=1);

namespace Menumbing\Tracer\Listener;

use Hyperf\Collection\Arr;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Stringable\Str;
use Hyperf\Tracer\SpanStarter;
use Hyperf\Tracer\SpanTagManager;
use Hyperf\Tracer\SwitchManager;

use const OpenTracing\Tags\SPAN_KIND_RPC_CLIENT;

/**
 * @author  Iqbal Maulana <iq.bluejack@gmail.com>
 */
class DbQueryExecutedListener implements ListenerInterface
{
    use SpanStarter;

    public function __construct(
        private readonly SwitchManager $switchManager,
        private readonly SpanTagManager $spanTagManager,
        private readonly ConfigInterface $config,
    ) {
    }

    public function listen(): array
    {
        return [
            QueryExecuted::class,
        ];
    }

    /**
     * @param QueryExecuted $event
     */
    public function process(object $event): void
    {
        if ($this->switchManager->isEnable('db_query') === false) {
            return;
        }

        $sql = $event->sql;
        if (! Arr::isAssoc($event->bindings)) {
            foreach ($event->bindings as $value) {
                $sql = Str::replaceFirst('?', "'{$value}'", $sql);
            }
        }

        $dbConfig = $this->getDbConfig($event->connectionName ?? 'default');

        $endTime = microtime(true);
        $span = $this->startSpan($this->spanTagManager->get('db', 'db.query'), [
            'start_time' => (int) (($endTime - $event->time / 1000) * 1000 * 1000),
        ], kind: SPAN_KIND_RPC_CLIENT);

        $span->setTag('db.system', $dbConfig['driver']);
        $span->setTag('db.name', $dbConfig['database'] ?? 'default');
        $span->setTag('db.connection', $event->connectionName ?? 'default');
        $span->setTag('db.operation', $this->parseOperation($sql));

        $span->setTag('server.address', $dbConfig['host'] ?? 'localhost');
        $span->setTag('server.port', $dbConfig['port']);

        $span->setTag($this->spanTagManager->get('db', 'db.statement'), $sql);
        $span->setTag($this->spanTagManager->get('db', 'db.query_time'), $event->time . ' ms');
        $span->finish((int) ($endTime * 1000 * 1000));
    }

    protected function getDbConfig(string $pool): array
    {
        return $this->config->get('databases.' . $pool, []);
    }

    protected function parseOperation(string $sql): string
    {
        $sql = preg_replace('/(--[^\n]*|\/\*.*?\*\/)/s', '', $sql);
        $sql = strtoupper(ltrim($sql));

        // Handle CTE
        if (str_starts_with($sql, 'WITH')) {
            if (preg_match('/\)\s*(SELECT|INSERT|UPDATE|DELETE)/', $sql, $m)) {
                return $m[1];
            }
        }

        if (preg_match('/^(SELECT|INSERT|UPDATE|DELETE|BEGIN|COMMIT|ROLLBACK)/', $sql, $m)) {
            return $m[1];
        }

        return 'OTHER';
    }
}
