<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Menumbing\Database\Resilience\Connectors;

use Closure;
use Hyperf\Database\Connection;
use Hyperf\Database\Connectors\ConnectionFactory as BaseConnectionFactory;
use Hyperf\Database\PgSQL\PostgreSqlConnection as BasePostgreSqlConnection;
use Hyperf\Database\PgSQL\PostgreSqlSwooleExtConnection as BasePostgreSqlSwooleExtConnection;
use Menumbing\Database\Resilience\PostgreSqlConnection;
use Menumbing\Database\Resilience\PostgreSqlSwooleExtConnection;

/**
 * Routes the pgsql and pgsql-swoole drivers to the resilient connection classes.
 *
 * Binding this factory is used instead of Connection::resolverFor() on purpose: the pgsql
 * component registers its own resolver from a BootApplication listener, and listeners that
 * share the default priority are pulled out of Hyperf\Event\ListenerProvider in a
 * non-deterministic order. Overriding createConnection() does not depend on listener ordering
 * at all, because ConnectionFactory::createConnection() consults the resolver itself.
 */
class ConnectionFactory extends BaseConnectionFactory
{
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        $factory = $this->resilientConnectionFactory($driver, $connection, $database, $prefix, $config);

        if ($factory === null) {
            return parent::createConnection($driver, $connection, $database, $prefix, $config);
        }

        $resolver = Connection::getResolver($driver);
        if ($resolver === null) {
            return $factory();
        }

        $resolved = $resolver($connection, $database, $prefix, $config);

        return $this->isPgSqlComponentConnection($driver, $resolved) ? $factory() : $resolved;
    }

    /**
     * Build the resilient counterpart of the given driver, or null when this package must stay
     * out of the way because the pgsql component is not installed.
     */
    protected function resilientConnectionFactory($driver, $connection, $database, $prefix, array $config): ?Closure
    {
        return match ($driver) {
            'pgsql-swoole' => $this->hasSwoolePgSqlConnection()
                ? static fn () => new PostgreSqlSwooleExtConnection($connection, $database, $prefix, $config)
                : null,
            'pgsql' => class_exists(BasePostgreSqlConnection::class)
                ? static fn () => new PostgreSqlConnection($connection, $database, $prefix, $config)
                : null,
            default => null,
        };
    }

    /**
     * PostgreSqlSwooleExtConnection defaults its $fetchMode property to SW_PGSQL_ASSOC, a constant
     * that only exists when Swoole is built with --enable-swoole-pgsql. class_exists() autoloads
     * the class, which would turn that missing constant into a fatal Error instead of a clean
     * "unsupported driver", so the constant has to be checked first.
     */
    protected function hasSwoolePgSqlConnection(): bool
    {
        return defined('SW_PGSQL_ASSOC') && class_exists(BasePostgreSqlSwooleExtConnection::class);
    }

    /**
     * Only the exact classes shipped by the pgsql component are replaced. An application that
     * registered its own resolver, or that subclasses the pgsql connection, keeps its own class.
     */
    protected function isPgSqlComponentConnection($driver, object $resolved): bool
    {
        $expected = match ($driver) {
            'pgsql-swoole' => BasePostgreSqlSwooleExtConnection::class,
            'pgsql' => BasePostgreSqlConnection::class,
            default => null,
        };

        return $expected !== null && $resolved::class === $expected;
    }
}
